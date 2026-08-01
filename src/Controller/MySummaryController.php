<?php

namespace App\Controller;

use App\Entity\Summary;
use App\Entity\User;
use App\Repository\SummaryRepository;
use App\Repository\SummaryLikeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MySummaryController extends AbstractController
{
    #[Route('/mes-resumes', name: 'app_my_summaries', methods: ['GET'])]
    public function index(SummaryRepository $summaryRepository, SummaryLikeRepository $summaryLikeRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $summaries = $summaryRepository->findOwnedForList($user);
        $eligible = [];
        foreach ($summaries as $summary) {
            if ($summary->getEpisode() !== null || $summary->getTome() !== null || $summary->getChapitre() !== null) {
                $eligible[$summary->getId()] = $summary;
            }
        }
        $states = $summaryRepository->buildCardStates($eligible, $user, $summaryLikeRepository);

        return $this->render('my_summary/index.html.twig', [
            'summary_cards' => array_map(
                fn (Summary $summary): array => $this->summaryCard($summary, $user, $states[$summary->getId()] ?? null),
                $summaries,
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function summaryCard(Summary $summary, User $viewer, ?array $likeState): array
    {
        $anime = $summary->getAnime()
            ?? $summary->getSeason()?->getAnime()
            ?? $summary->getEpisode()?->getSeason()?->getAnime();
        $manga = $summary->getManga()
            ?? $summary->getTome()?->getManga()
            ?? $summary->getChapitre()?->getManga();

        [$route, $parameters] = match (true) {
            $summary->getAnime() !== null => ['app_anime_show', ['id' => $anime->getId()]],
            $summary->getManga() !== null => ['app_manga_show', ['id' => $manga->getId()]],
            $summary->getSeason() !== null && $summary->getSeason()->getOwner()?->getId() === $viewer->getId()
                => ['app_private_season_show', ['id' => $summary->getSeason()->getId()]],
            $summary->getSeason() !== null => ['app_anime_show', ['id' => $anime->getId()]],
            $summary->getEpisode() !== null && $summary->getEpisode()->getSeason()?->getOwner()?->getId() === $viewer->getId()
                => ['app_private_season_show', ['id' => $summary->getEpisode()->getSeason()->getId(), '_fragment' => 'episodes']],
            $summary->getEpisode() !== null => ['app_anime_show', ['id' => $anime->getId()]],
            $summary->getTome() !== null => ['app_manga_show', ['id' => $manga->getId(), '_fragment' => 'tomes']],
            $summary->getChapitre() !== null => ['app_manga_show', ['id' => $manga->getId(), '_fragment' => 'chapitres']],
            default => [null, []],
        };

        $parent = $summary->getAnime() ?? $summary->getManga() ?? $summary->getSeason()
            ?? $summary->getEpisode() ?? $summary->getTome() ?? $summary->getChapitre();
        [$editRoute, $editId] = match (true) {
            $summary->getAnime() !== null => ['app_forms_anime_edit', $summary->getAnime()->getId()],
            $summary->getManga() !== null => ['app_forms_manga_edit', $summary->getManga()->getId()],
            $summary->getSeason() !== null => ['app_forms_season_edit', $summary->getSeason()->getId()],
            $summary->getEpisode() !== null => ['app_forms_episode_edit', $summary->getEpisode()->getId()],
            $summary->getTome() !== null => ['app_forms_tome_edit', $summary->getTome()->getId()],
            $summary->getChapitre() !== null => ['app_forms_chapitre_edit', $summary->getChapitre()->getId()],
            default => [null, null],
        };

        return [
            'typeLabel' => $anime !== null ? 'Anime' : 'Manga',
            'title' => $summary->getTitle(),
            'description' => $summary->getContent(),
            'parentLabel' => $parent?->getTitle() ?? 'Contenu associé indisponible',
            'image' => $summary->getAnime()?->getCoverAnimeUrl()
                ?? $summary->getManga()?->getCoverMangaUrl()
                ?? $summary->getSeason()?->getCoverSeasonUrl()
                ?? $summary->getEpisode()?->getCoverEpisodeUrl()
                ?? $summary->getTome()?->getCoverTomeUrl()
                ?? $summary->getChapitre()?->getCoverChapitreUrl(),
            'openRoute' => $route,
            'openParameters' => $parameters,
            'likeState' => $likeState,
            'editRoute' => $editRoute,
            'editId' => $editId,
        ];
    }
}
