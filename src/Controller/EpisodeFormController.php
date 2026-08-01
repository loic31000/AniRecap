<?php

namespace App\Controller;

use App\Dto\EpisodeInput;
use App\Entity\Diaporama;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Slide;
use App\Entity\User;
use App\Form\EpisodeType;
use App\Repository\EpisodeRepository;
use App\Repository\SeasonRepository;
use App\Repository\SummaryRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class EpisodeFormController extends AbstractController
{
    #[Route(
        '/formulaires/saisons/{seasonId}/episodes/ajouter',
        name: 'app_forms_episode_create',
        methods: ['GET', 'POST'],
        requirements: ['seasonId' => '\d+'],
    )]
    public function create(
        int $seasonId,
        Request $request,
        SeasonRepository $seasonRepository,
        EpisodeRepository $episodeRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $season = $seasonRepository->findOneOwned($seasonId, $user);
        if ($season === null) {
            throw $this->createNotFoundException();
        }

        $input = new EpisodeInput();
        $form = $this->createForm(EpisodeType::class, $input, [
            'validation_groups' => ['Default', 'create'],
            'csrf_token_id' => 'episode_create_' . $season->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($episodeRepository->numberExistsForSeason($season, $input->number)) {
                $form->get('number')->addError(new FormError('Ce numéro d’épisode existe déjà dans cette saison.'));
            } else {
                $start = $this->parseTimecode($input->startTimecode);
                $end = $input->endTimecode !== null && $input->endTimecode !== ''
                    ? $this->parseTimecode($input->endTimecode)
                    : null;

                if ($end !== null && $end < $start) {
                    $form->get('endTimecode')->addError(new FormError('Le timecode de fin doit être supérieur ou égal au début.'));
                } else {
                    return $this->createEpisodeWithDiaporama(
                        $season,
                        $user,
                        $input,
                        $start,
                        $end,
                        $entityManager,
                        $imageUploader,
                    );
                }
            }
        }

        return $this->render('forms/episode.html.twig', [
            'form' => $form,
            'season' => $season,
            'is_edit' => false,
            'current_cover_url' => null,
        ]);
    }

    private function createEpisodeWithDiaporama(
        Season $season,
        User $user,
        EpisodeInput $input,
        int $start,
        ?int $end,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $filename = null;
        $episode = new Episode();
        $diaporama = new Diaporama();
        $slide = new Slide();

        try {
            if (!$input->image instanceof UploadedFile) {
                throw new \RuntimeException('L’image de la première scène est obligatoire.');
            }

            $filename = $imageUploader->store($input->image);
            $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use (
                $episode,
                $diaporama,
                $slide,
                $season,
                $user,
                $input,
                $filename,
                $start,
                $end,
            ): void {
                $episode
                    ->setSeason($season)
                    ->setUser($user);
                $this->applyInput($episode, $input, null);

                $diaporama
                    ->setTitle($input->title)
                    ->setContent($input->description)
                    ->setSourceType(Diaporama::SOURCE_ANIME)
                    ->setUser($user);

                $slide
                    ->setDiaporama($diaporama)
                    ->setEpisode($episode)
                    ->setTitle($input->title)
                    ->setContent($input->description)
                    ->setImageFilename($filename)
                    ->setPosition(1)
                    ->setSpoilerLevel($input->spoilerLevel)
                    ->setStartTimecodeSeconds($start)
                    ->setEndTimecodeSeconds($end);

                $entityManager->persist($episode);
                $entityManager->persist($diaporama);
                $entityManager->persist($slide);
                $entityManager->flush();

                $episode->setCoverEpisodeUrl($this->generateUrl('app_diaporama_slide_image', [
                    'diaporamaId' => $diaporama->getId(),
                    'slideId' => $slide->getId(),
                ]));
            });
        } catch (\Throwable) {
            if ($filename !== null) {
                $imageUploader->remove($filename);
            }

            $this->addFlash('error', 'L’épisode et son diaporama n’ont pas pu être enregistrés. Veuillez réessayer.');

            return $this->redirectToRoute('app_forms_episode_create', ['seasonId' => $season->getId()]);
        }

        $this->addFlash('success', 'L’épisode et son diaporama ont été créés avec succès.');

        return $this->redirectToRoute('app_private_season_show', [
            'id' => $season->getId(),
            '_fragment' => 'episodes',
        ]);
    }

    #[Route(
        '/formulaires/episodes/{id}/modifier',
        name: 'app_forms_episode_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+'],
    )]
    public function edit(
        int $id,
        Request $request,
        EpisodeRepository $episodeRepository,
        SummaryRepository $summaryRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $episode = $episodeRepository->findOneOwned($id, $user);
        if ($episode === null || !$episode->getSeason() instanceof Season) {
            throw $this->createNotFoundException();
        }

        $season = $episode->getSeason();
        $input = $this->createInputFromEpisode($episode);
        $form = $this->createForm(EpisodeType::class, $input, [
            'is_edit' => true,
            'csrf_token_id' => 'episode_edit_' . $episode->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($episodeRepository->numberExistsForSeason($season, $input->number, $episode->getId())) {
                $form->get('number')->addError(new FormError('Ce numéro d’épisode existe déjà dans cette saison.'));
            } else {
                $newFilename = null;
                $oldCoverUrl = $episode->getCoverEpisodeUrl();
                $coverUrl = $oldCoverUrl;

                try {
                    if ($input->image instanceof UploadedFile) {
                        $newFilename = $imageUploader->store($input->image);
                        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $newFilename]);
                    }

                    $entityManager->wrapInTransaction(function () use ($episode, $season, $user, $input, $coverUrl, $summaryRepository): void {
                        $episode
                            ->setSeason($season)
                            ->setUser($user);
                        $this->applyInput($episode, $input, $coverUrl);
                        $summaryRepository->synchronizeOwnedForParent(
                            'episode',
                            $episode,
                            $user,
                            $input->description,
                            spoilerLevel: $input->spoilerLevel->value,
                        );
                    });
                } catch (\Throwable) {
                    if ($newFilename !== null) {
                        $imageUploader->remove($newFilename);
                    }

                    $this->addFlash('error', 'Les modifications n’ont pas pu être enregistrées. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_episode_edit', ['id' => $episode->getId()]);
                }

                if ($newFilename !== null && $oldCoverUrl !== null) {
                    $oldFilename = basename((string) parse_url($oldCoverUrl, PHP_URL_PATH));
                    $imageUploader->remove($oldFilename);
                }

                $this->addFlash('success', 'L’épisode a été modifié avec succès.');

                return $this->redirectToRoute('app_private_season_show', [
                    'id' => $season->getId(),
                    '_fragment' => 'episodes',
                ]);
            }
        }

        return $this->render('forms/episode.html.twig', [
            'form' => $form,
            'season' => $season,
            'is_edit' => true,
            'current_cover_url' => $episode->getCoverEpisodeUrl(),
        ]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function applyInput(Episode $episode, EpisodeInput $input, ?string $coverUrl): void
    {
        $episode
            ->setTitle($input->title)
            ->setNumber($input->number)
            ->setSynopsis($input->description)
            ->setCoverEpisodeUrl($coverUrl)
            ->setType($input->type)
            ->setStatus($input->status)
            ->setAuthor($input->author)
            ->setEpisodeDate($input->releaseYear)
            ->setSpoilerLevel($input->spoilerLevel);

        foreach ($episode->getCategorie()->toArray() as $category) {
            if (!in_array($category, $input->categories, true)) {
                $episode->removeCategorie($category);
            }
        }

        foreach ($input->categories as $category) {
            $episode->addCategorie($category);
        }
    }

    private function createInputFromEpisode(Episode $episode): EpisodeInput
    {
        $input = new EpisodeInput();
        $input->title = $episode->getTitle();
        $input->number = $episode->getNumber();
        $input->description = $episode->getSynopsis();
        $input->type = $episode->getType();
        $input->status = $episode->getStatus();
        $input->author = $episode->getAuthor();
        $input->releaseYear = $episode->getEpisodeDate();
        $input->spoilerLevel = $episode->getSpoilerLevel();
        $input->categories = $episode->getCategorie()->toArray();

        return $input;
    }

    private function parseTimecode(string $timecode): int
    {
        $parts = array_map('intval', explode(':', $timecode));

        return count($parts) === 2
            ? ($parts[0] * 60) + $parts[1]
            : ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
    }
}
