<?php

namespace App\Controller;

use App\Dto\EpisodeInput;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\User;
use App\Form\EpisodeType;
use App\Repository\EpisodeRepository;
use App\Repository\SeasonRepository;
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
                $filename = null;
                $episode = new Episode();

                try {
                    if (!$input->image instanceof UploadedFile) {
                        throw new \RuntimeException('La miniature est obligatoire.');
                    }

                    $filename = $imageUploader->store($input->image);
                    $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);

                    $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($episode, $season, $user, $input, $coverUrl): void {
                        $episode
                            ->setSeason($season)
                            ->setUser($user);
                        $this->applyInput($episode, $input, $coverUrl);
                        $entityManager->persist($episode);
                    });
                } catch (\Throwable) {
                    if ($filename !== null) {
                        $imageUploader->remove($filename);
                    }

                    $this->addFlash('error', 'L’épisode n’a pas pu être enregistré. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_episode_create', ['seasonId' => $season->getId()]);
                }

                $this->addFlash('success', 'L’épisode a été créé avec succès.');

                return $this->redirectToRoute('app_private_season_show', [
                    'id' => $season->getId(),
                    '_fragment' => 'episodes',
                ]);
            }
        }

        return $this->render('forms/episode.html.twig', [
            'form' => $form,
            'season' => $season,
            'is_edit' => false,
            'current_cover_url' => null,
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

                    $entityManager->wrapInTransaction(function () use ($episode, $season, $user, $input, $coverUrl): void {
                        $episode
                            ->setSeason($season)
                            ->setUser($user);
                        $this->applyInput($episode, $input, $coverUrl);
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
}
