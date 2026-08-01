<?php

namespace App\Controller;

use App\Dto\MangaSceneInput;
use App\Dto\SeasonSceneInput;
use App\Entity\Diaporama;
use App\Entity\Slide;
use App\Entity\User;
use App\Enum\SpoilerLevel;
use App\Form\MangaSceneType;
use App\Form\SeasonSceneType;
use App\Repository\ChapitreRepository;
use App\Repository\DiaporamaRepository;
use App\Repository\EpisodeRepository;
use App\Repository\SlideRepository;
use App\Repository\TomeRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SceneFormController extends AbstractController
{
    #[Route('/formulaires/scene-saison', name: 'app_forms_season_scene', methods: ['GET', 'POST'])]
    #[Route('/diaporamas/{id}/scenes/anime/ajouter', name: 'app_diaporama_scene_anime_create', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function createAnime(
        Request $request,
        DiaporamaRepository $diaporamaRepository,
        EpisodeRepository $episodeRepository,
        SlideRepository $slideRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
        ?int $id = null,
    ): Response {
        $user = $this->requireUser();
        $diaporama = $id !== null ? $diaporamaRepository->findOneOwned($id, $user) : null;
        if ($id !== null && ($diaporama === null || $diaporama->getSourceType() !== Diaporama::SOURCE_ANIME)) {
            throw $this->createNotFoundException();
        }

        $episodes = $episodeRepository->findOwnedForSceneSelection($user);
        $choices = [];
        foreach ($episodes as $episode) {
            $season = $episode->getSeason();
            $anime = $season?->getAnime();
            $choices[sprintf('%s • Saison %d • Épisode %d', $anime?->getTitle(), $season?->getNumber(), $episode->getNumber())] = $episode->getId();
        }

        $input = new SeasonSceneInput();
        $form = $this->createForm(SeasonSceneType::class, $input, [
            'episode_choices' => $choices,
            'csrf_token_id' => 'slide_anime_create',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $submittedEpisodeId = $this->submittedPositiveId($request, $form->getName(), 'episodeId');
            if ($submittedEpisodeId !== null && $episodeRepository->findOneOwned($submittedEpisodeId, $user) === null) {
                throw $this->createNotFoundException();
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $episode = $episodeRepository->findOneOwned($input->episodeId, $user);
            if ($episode === null) {
                throw $this->createNotFoundException();
            }

            $isInitialScene = $diaporama === null;
            if ($isInitialScene) {
                $diaporama = (new Diaporama())
                    ->setTitle($input->title)
                    ->setContent($input->content)
                    ->setSourceType(Diaporama::SOURCE_ANIME)
                    ->setUser($user);
            }

            $start = $this->parseTimecode($input->startTimecode);
            $end = $input->endTimecode !== null && $input->endTimecode !== ''
                ? $this->parseTimecode($input->endTimecode)
                : null;

            if ($end !== null && $end < $start) {
                $form->get('endTimecode')->addError(new FormError('Le timecode de fin doit être supérieur ou égal au début.'));
            } else {
                return $this->persistSlide(
                    $diaporama,
                    $input->image,
                    $input->title,
                    $input->content,
                    $input->spoilerLevel,
                    $slideRepository,
                    $entityManager,
                    $imageUploader,
                    $isInitialScene,
                    $this->safeReturnTo($request),
                    static fn (Slide $slide): Slide => $slide
                        ->setEpisode($episode)
                        ->setStartTimecodeSeconds($start)
                        ->setEndTimecodeSeconds($end),
                );
            }
        }

        return $this->render('forms/season_scene.html.twig', [
            'form' => $form,
            'diaporama' => $diaporama,
        ]);
    }

    #[Route('/formulaires/scene-manga', name: 'app_forms_manga_scene', methods: ['GET', 'POST'])]
    #[Route('/diaporamas/{id}/scenes/manga/ajouter', name: 'app_diaporama_scene_manga_create', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function createManga(
        Request $request,
        DiaporamaRepository $diaporamaRepository,
        TomeRepository $tomeRepository,
        ChapitreRepository $chapitreRepository,
        SlideRepository $slideRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
        ?int $id = null,
    ): Response {
        $user = $this->requireUser();
        $diaporama = $id !== null ? $diaporamaRepository->findOneOwned($id, $user) : null;
        if ($id !== null && ($diaporama === null || $diaporama->getSourceType() !== Diaporama::SOURCE_MANGA)) {
            throw $this->createNotFoundException();
        }

        $tomeChoices = [];
        foreach ($tomeRepository->findOwnedForSceneSelection($user) as $tome) {
            $tomeChoices[sprintf('%s • Tome %d', $tome->getManga()?->getTitle(), $tome->getNumber())] = $tome->getId();
        }

        $chapitreChoices = [];
        foreach ($chapitreRepository->findOwnedForSceneSelection($user) as $chapitre) {
            $chapitreChoices[sprintf('%s • Chapitre %d', $chapitre->getManga()?->getTitle(), $chapitre->getNumber())] = $chapitre->getId();
        }

        $input = new MangaSceneInput();
        $form = $this->createForm(MangaSceneType::class, $input, [
            'tome_choices' => $tomeChoices,
            'chapitre_choices' => $chapitreChoices,
            'csrf_token_id' => 'slide_manga_create',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $submittedTomeId = $this->submittedPositiveId($request, $form->getName(), 'tomeId');
            $submittedChapitreId = $this->submittedPositiveId($request, $form->getName(), 'chapitreId');
            if (($submittedTomeId !== null && $tomeRepository->findOneOwned($submittedTomeId, $user) === null)
                || ($submittedChapitreId !== null && $chapitreRepository->findOneOwned($submittedChapitreId, $user) === null)
            ) {
                throw $this->createNotFoundException();
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $hasTome = $input->targetType === 'tome' && $input->tomeId !== null && $input->chapitreId === null;
            $hasChapitre = $input->targetType === 'chapitre' && $input->chapitreId !== null && $input->tomeId === null;

            if (!$hasTome && !$hasChapitre) {
                $form->get('targetType')->addError(new FormError('Choisissez exclusivement un Tome ou un Chapitre.'));
            } else {
                $tome = $hasTome ? $tomeRepository->findOneOwned($input->tomeId, $user) : null;
                $chapitre = $hasChapitre ? $chapitreRepository->findOneOwned($input->chapitreId, $user) : null;
                if (($hasTome && $tome === null) || ($hasChapitre && $chapitre === null)) {
                    throw $this->createNotFoundException();
                }

                $isInitialScene = $diaporama === null;
                if ($isInitialScene) {
                    $diaporama = (new Diaporama())
                        ->setTitle($input->title)
                        ->setContent($input->content)
                        ->setSourceType(Diaporama::SOURCE_MANGA)
                        ->setUser($user);
                }

                return $this->persistSlide(
                    $diaporama,
                    $input->image,
                    $input->title,
                    $input->content,
                    $input->spoilerLevel,
                    $slideRepository,
                    $entityManager,
                    $imageUploader,
                    $isInitialScene,
                    $this->safeReturnTo($request),
                    static fn (Slide $slide): Slide => $slide->setTome($tome)->setChapitre($chapitre),
                );
            }
        }

        return $this->render('forms/manga_scene.html.twig', [
            'form' => $form,
            'diaporama' => $diaporama,
        ]);
    }

    private function persistSlide(
        Diaporama $diaporama,
        ?UploadedFile $image,
        ?string $title,
        ?string $content,
        SpoilerLevel $spoilerLevel,
        SlideRepository $slideRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
        bool $isInitialScene,
        ?string $returnTo,
        \Closure $setTarget,
    ): Response {
        $filename = null;

        try {
            if (!$image instanceof UploadedFile) {
                throw new \RuntimeException('L’image de scène est obligatoire.');
            }

            $filename = $imageUploader->store($image);
            $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use (
                $diaporama,
                $title,
                $content,
                $spoilerLevel,
                $filename,
                $slideRepository,
                $setTarget,
                $isInitialScene,
            ): void {
                if ($isInitialScene) {
                    $entityManager->persist($diaporama);
                    $position = 1;
                } else {
                    $entityManager->lock($diaporama, LockMode::PESSIMISTIC_WRITE);
                    $position = $slideRepository->nextPosition($diaporama);
                }

                $slide = (new Slide())
                    ->setDiaporama($diaporama)
                    ->setTitle($title)
                    ->setContent($content)
                    ->setImageFilename($filename)
                    ->setPosition($position)
                    ->setSpoilerLevel($spoilerLevel);
                $setTarget($slide);
                $entityManager->persist($slide);
            });
        } catch (\Throwable) {
            if ($filename !== null) {
                $imageUploader->remove($filename);
            }

            $this->addFlash('error', 'La scène n’a pas pu être enregistrée. Veuillez réessayer.');

            return $this->redirectToRoute(
                $isInitialScene
                    ? ($diaporama->getSourceType() === Diaporama::SOURCE_ANIME
                        ? 'app_forms_season_scene'
                        : 'app_forms_manga_scene')
                    : ($diaporama->getSourceType() === Diaporama::SOURCE_ANIME
                        ? 'app_diaporama_scene_anime_create'
                        : 'app_diaporama_scene_manga_create'),
                array_filter([
                    'id' => $isInitialScene ? null : $diaporama->getId(),
                    'return_to' => $returnTo,
                ], static fn (mixed $value): bool => $value !== null),
            );
        }

        $this->addFlash('success', 'La scène a été ajoutée au diaporama.');

        return $this->redirectToRoute('app_diaporama_show', array_filter([
            'id' => $diaporama->getId(),
            'return_to' => $returnTo,
        ], static fn (mixed $value): bool => $value !== null));
    }

    private function safeReturnTo(Request $request): ?string
    {
        $returnTo = (string) $request->query->get('return_to', '');

        return str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//') ? $returnTo : null;
    }

    private function parseTimecode(string $timecode): int
    {
        $parts = array_map('intval', explode(':', $timecode));

        return count($parts) === 2
            ? ($parts[0] * 60) + $parts[1]
            : ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
    }

    private function submittedPositiveId(Request $request, string $formName, string $field): ?int
    {
        $submittedForm = $request->request->all($formName);
        $value = $submittedForm[$field] ?? null;
        if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
