<?php

namespace App\Controller;

use App\Dto\SeasonInput;
use App\Entity\Anime;
use App\Entity\Season;
use App\Entity\User;
use App\Form\SeasonType;
use App\Repository\SeasonRepository;
use App\Repository\SummaryRepository;
use App\Repository\EpisodeRepository;
use App\Repository\DiaporamaRepository;
use App\Repository\SlideRepository;
use App\Repository\CharacterRepository;
use App\Repository\SummaryLikeRepository;
use App\Enum\SpoilerLevel;
use App\Service\SynopsisImageUploader;
use App\Service\OwnedContentDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SeasonFormController extends AbstractController
{
    #[Route('/formulaires/saison', name: 'app_forms_season', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        SeasonRepository $seasonRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $input = new SeasonInput();
        $form = $this->createForm(SeasonType::class, $input, [
            'owner' => $user,
            'validation_groups' => ['Default', 'create'],
            'csrf_token_id' => 'season_create',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isOwnedPrivateAnime($input->anime, $user)) {
                throw $this->createNotFoundException();
            }

            if ($seasonRepository->numberExistsForAnime($input->anime, $input->number)) {
                $form->get('number')->addError(new FormError('Ce numéro de saison existe déjà pour cet animé.'));
            } else {
                $filename = null;
                $season = new Season();

                try {
                    if (!$input->image instanceof UploadedFile) {
                        throw new \RuntimeException('La miniature est obligatoire.');
                    }

                    $filename = $imageUploader->store($input->image);
                    $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);

                    $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($input, $season, $coverUrl): void {
                        $this->applyInput($season, $input, $coverUrl);
                        $entityManager->persist($season);
                    });
                } catch (\Throwable) {
                    if ($filename !== null) {
                        $imageUploader->remove($filename);
                    }

                    $this->addFlash('error', 'La saison n’a pas pu être enregistrée. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_season');
                }

                $this->addFlash('success', 'La saison a été créée avec succès.');

                return $this->redirectToRoute('app_private_season_show', ['id' => $season->getId()]);
            }
        }

        return $this->render('forms/season.html.twig', [
            'form' => $form,
            'is_edit' => false,
            'current_cover_url' => null,
        ]);
    }

    #[Route('/formulaires/saisons/{id}/supprimer', name: 'app_forms_season_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request, SeasonRepository $seasonRepository, OwnedContentDeletionService $deletionService): Response
    {
        $user = $this->requireUser();
        $season = $seasonRepository->findOneOwned($id, $user);
        if ($season === null) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('season_delete_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $animeId = $season->getAnime()?->getId();
        $deletionService->deleteSeason($season);
        $this->addFlash('success', 'La saison et ses épisodes ont été supprimés.');

        return $animeId !== null
            ? $this->redirectToRoute('app_anime_show', ['id' => $animeId])
            : $this->redirectToRoute('app_catalogue');
    }

    #[Route('/formulaires/saisons/{id}/modifier', name: 'app_forms_season_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        SeasonRepository $seasonRepository,
        SummaryRepository $summaryRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $season = $seasonRepository->findOneOwned($id, $user);
        if ($season === null) {
            throw $this->createNotFoundException();
        }

        $input = $this->createInputFromSeason($season);
        $form = $this->createForm(SeasonType::class, $input, [
            'owner' => $user,
            'is_edit' => true,
            'csrf_token_id' => 'season_edit_' . $season->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isOwnedAnime($input->anime, $user)) {
                throw $this->createNotFoundException();
            }

            if ($seasonRepository->numberExistsForAnime($input->anime, $input->number, $season->getId())) {
                $form->get('number')->addError(new FormError('Ce numéro de saison existe déjà pour cet animé.'));
            } else {
                $newFilename = null;
                $oldCoverUrl = $season->getCoverSeasonUrl();
                $coverUrl = $oldCoverUrl;

                try {
                    if ($input->image instanceof UploadedFile) {
                        $newFilename = $imageUploader->store($input->image);
                        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $newFilename]);
                    }

                    $entityManager->wrapInTransaction(function () use ($season, $input, $coverUrl, $summaryRepository, $user): void {
                        $this->applyInput($season, $input, $coverUrl);
                        $summaryRepository->synchronizeOwnedForParent('season', $season, $user, $input->description);
                    });
                } catch (\Throwable) {
                    if ($newFilename !== null) {
                        $imageUploader->remove($newFilename);
                    }

                    $this->addFlash('error', 'Les modifications n’ont pas pu être enregistrées. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_season_edit', ['id' => $season->getId()]);
                }

                if ($newFilename !== null && $oldCoverUrl !== null) {
                    $oldFilename = basename((string) parse_url($oldCoverUrl, PHP_URL_PATH));
                    $imageUploader->remove($oldFilename);
                }

                $this->addFlash('success', 'La saison a été modifiée avec succès.');

                return $this->redirectToRoute('app_private_season_show', ['id' => $season->getId()]);
            }
        }

        return $this->render('forms/season.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'current_cover_url' => $season->getCoverSeasonUrl(),
        ]);
    }

    #[Route('/saisons/{id}', name: 'app_private_season_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        SeasonRepository $seasonRepository,
        EpisodeRepository $episodeRepository,
        DiaporamaRepository $diaporamaRepository,
        SlideRepository $slideRepository,
        CharacterRepository $characterRepository,
        SummaryRepository $summaryRepository,
        SummaryLikeRepository $summaryLikeRepository,
    ): Response
    {
        $user = $this->requireUser();
        $season = $seasonRepository->findOneOwned($id, $user);
        if ($season === null) {
            throw $this->createNotFoundException();
        }

        $episodes = $episodeRepository->findOwnedBySeason($season, $user);
        $episodeIds = array_map(
            static fn ($episode): int => (int) $episode->getId(),
            $episodes,
        );
        $slideLevels = $slideRepository->findHighestLevelsForEpisodes($episodeIds);
        $episodeSummaries = $summaryRepository->findForParents('episode', $episodeIds, $user);
        $effectiveLevels = [];
        foreach ($episodes as $episode) {
            $effectiveLevels[$episode->getId()] = $this->highestLevel(
                $episode->getSpoilerLevel(),
                $slideLevels[$episode->getId()] ?? SpoilerLevel::Aucun,
            );
        }

        return $this->render('season/private_show.html.twig', [
            'season' => $season,
            'episodes' => $episodes,
            'episode_diaporamas' => $diaporamaRepository->findOwnedLinksForEpisodes($episodeIds, $user),
            'episode_spoiler_levels' => $effectiveLevels,
            'characters' => $characterRepository->findOwnedBySeason($season, $user),
            'episode_summary_states' => $summaryRepository->buildCardStates($episodeSummaries, $user, $summaryLikeRepository),
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

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function isOwnedPrivateAnime(?Anime $anime, User $user): bool
    {
        return $anime !== null
            && $anime->getOwner()?->getId() === $user->getId()
            && $anime->isPublic() === false;
    }

    private function isOwnedAnime(?Anime $anime, User $user): bool
    {
        return $anime !== null && $anime->getOwner()?->getId() === $user->getId();
    }

    private function applyInput(Season $season, SeasonInput $input, ?string $coverUrl): void
    {
        $season
            ->setAnime($input->anime)
            ->setTitle($input->title)
            ->setNumber($input->number)
            ->setSynopsis($input->description)
            ->setCoverSeasonUrl($coverUrl)
            ->setType($input->type)
            ->setStatus($input->status)
            ->setAuthor($input->author)
            ->setSeasonDate($input->releaseYear);

        foreach ($season->getCategorie()->toArray() as $category) {
            if (!in_array($category, $input->categories, true)) {
                $season->removeCategorie($category);
            }
        }

        foreach ($input->categories as $category) {
            $season->addCategorie($category);
        }
    }

    private function createInputFromSeason(Season $season): SeasonInput
    {
        $input = new SeasonInput();
        $input->anime = $season->getAnime();
        $input->title = $season->getTitle();
        $input->number = $season->getNumber();
        $input->description = $season->getSynopsis();
        $input->type = $season->getType();
        $input->status = $season->getStatus();
        $input->author = $season->getAuthor();
        $input->releaseYear = $season->getSeasonDate();
        $input->categories = $season->getCategorie()->toArray();

        return $input;
    }
}
