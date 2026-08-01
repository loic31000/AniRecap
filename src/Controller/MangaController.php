<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MangaRepository;
use App\Repository\TomeRepository;
use App\Repository\ChapitreRepository;
use App\Repository\DiaporamaRepository;
use App\Repository\SlideRepository;
use App\Repository\FavoriteRepository;
use App\Repository\CharacterRepository;
use App\Repository\SummaryRepository;
use App\Repository\SummaryLikeRepository;
use App\Enum\SpoilerLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MangaController extends AbstractController
{
    #[Route('/manga/{id}', name: 'app_manga_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        MangaRepository $mangaRepository,
        TomeRepository $tomeRepository,
        ChapitreRepository $chapitreRepository,
        DiaporamaRepository $diaporamaRepository,
        SlideRepository $slideRepository,
        FavoriteRepository $favoriteRepository,
        CharacterRepository $characterRepository,
        SummaryRepository $summaryRepository,
        SummaryLikeRepository $summaryLikeRepository,
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $manga = $mangaRepository->findOneVisibleTo($id, $user);
        if ($manga === null) {
            throw $this->createNotFoundException();
        }

        $isOwner = $manga->getOwner()?->getId() === $user->getId();
        $isOwnerPrivate = $isOwner && !$manga->isPublic();
        $tomes = $isOwner ? $tomeRepository->findOwnedByManga($manga, $user) : [];
        $chapitres = $isOwner ? $chapitreRepository->findOwnedByManga($manga, $user) : [];
        if (!$isOwner) {
            foreach ($summaryRepository->findVisibleChildSummariesForManga($manga, $user) as $summary) {
                if ($summary->getTome() !== null) { $tomes[$summary->getTome()->getId()] = $summary->getTome(); }
                if ($summary->getChapitre() !== null) { $chapitres[$summary->getChapitre()->getId()] = $summary->getChapitre(); }
            }
            $tomes = array_values($tomes);
            $chapitres = array_values($chapitres);
            usort($tomes, static fn ($a, $b): int => $a->getNumber() <=> $b->getNumber());
            usort($chapitres, static fn ($a, $b): int => $a->getNumber() <=> $b->getNumber());
        }
        $tomeIds = array_map(static fn ($tome): int => (int) $tome->getId(), $tomes);
        $chapitreIds = array_map(static fn ($chapitre): int => (int) $chapitre->getId(), $chapitres);
        $tomeSlideLevels = $isOwner ? $slideRepository->findHighestLevelsForTomes($tomeIds) : [];
        $chapitreSlideLevels = $isOwner ? $slideRepository->findHighestLevelsForChapitres($chapitreIds) : [];
        $tomeLevels = [];
        foreach ($tomes as $tome) {
            $tomeLevels[$tome->getId()] = $this->highestLevel($tome->getSpoilerLevel(), $tomeSlideLevels[$tome->getId()] ?? SpoilerLevel::Aucun);
        }
        $chapitreLevels = [];
        foreach ($chapitres as $chapitre) {
            $chapitreLevels[$chapitre->getId()] = $this->highestLevel($chapitre->getSpoilerLevel(), $chapitreSlideLevels[$chapitre->getId()] ?? SpoilerLevel::Aucun);
        }
        $favoriteState = $favoriteRepository->findRootFavoriteStates($user, [], [$manga->getId()]);
        $tomeSummaries = $summaryRepository->findForParents('tome', $tomeIds, $user);
        $chapitreSummaries = $summaryRepository->findForParents('chapitre', $chapitreIds, $user);

        return $this->render('manga/index.html.twig', [
            'manga' => $manga,
            'is_owner' => $isOwner,
            'is_owner_private' => $isOwnerPrivate,
            'tomes' => $tomes,
            'chapitres' => $chapitres,
            'tome_diaporamas' => $isOwner ? $diaporamaRepository->findOwnedLinksForTomes($tomeIds, $user) : [],
            'chapitre_diaporamas' => $isOwner ? $diaporamaRepository->findOwnedLinksForChapitres($chapitreIds, $user) : [],
            'characters' => $characterRepository->findOwnedByManga($manga, $user),
            'tome_spoiler_levels' => $tomeLevels,
            'chapitre_spoiler_levels' => $chapitreLevels,
            'favorite_oeuvre' => ['id' => $manga->getId(), 'type' => 'manga', 'title' => $manga->getTitle(), 'isFavorite' => $favoriteState['manga'][$manga->getId()] ?? false],
            'tome_summary_states' => $summaryRepository->buildCardStates($tomeSummaries, $user, $summaryLikeRepository),
            'chapitre_summary_states' => $summaryRepository->buildCardStates($chapitreSummaries, $user, $summaryLikeRepository),
        ]);
    }

    private function highestLevel(SpoilerLevel $first, SpoilerLevel $second): SpoilerLevel
    {
        $rank = static fn (SpoilerLevel $level): int => match ($level) {
            SpoilerLevel::Aucun => 0,
            SpoilerLevel::Mineur => 1,
            SpoilerLevel::Majeur => 2,
        };

        return $rank($first) >= $rank($second) ? $first : $second;
    }
}
