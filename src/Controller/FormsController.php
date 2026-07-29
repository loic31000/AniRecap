<?php

namespace App\Controller;

use App\Dto\AnimeSynopsisInput;
use App\Dto\MangaSynopsisInput;
use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\Summary;
use App\Entity\User;
use App\Form\AnimeSynopsisType;
use App\Form\MangaSynopsisType;
use App\Repository\AnimeRepository;
use App\Repository\MangaRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class FormsController extends AbstractController
{
    #[Route('/formulaires', name: 'app_forms_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('forms/index.html.twig');
    }

    #[Route('/formulaires/synopsis-anime', name: 'app_forms_anime_synopsis', methods: ['GET', 'POST'])]
    public function animeSynopsis(
        Request $request,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $input = new AnimeSynopsisInput();
        $form = $this->createForm(AnimeSynopsisType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filename = null;

            try {
                $filename = $imageUploader->store($input->image);
                $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);

                $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($input, $user, $coverUrl): void {
                    $anime = (new Anime())
                        ->setTitle($input->title)
                        ->setSynopsis($input->synopsis)
                        ->setCoverAnimeUrl($coverUrl)
                        ->setType('Anime')
                        ->setStatus($input->status)
                        ->setAuthor($input->author)
                        ->setAnimeDate((int) $input->releaseDate->format('Y'))
                        ->setReleaseDate($input->releaseDate)
                        ->setInitialSeasonNumber($input->initialSeasonNumber)
                        ->setEpisodeCount($input->episodeCount)
                        ->setIsPublic(false)
                        ->setOwner($user);

                    foreach ($input->categories as $category) {
                        $anime->addCategorie($category);
                    }

                    $summary = (new Summary())
                        ->setTitle($input->title)
                        ->setContent($input->synopsis)
                        ->setUser($user)
                        ->setAnime($anime);

                    $entityManager->persist($anime);
                    $entityManager->persist($summary);
                });
            } catch (\Throwable) {
                if ($filename !== null) {
                    $imageUploader->remove($filename);
                }

                $this->addFlash('error', 'Le synopsis anime n’a pas pu être enregistré. Veuillez réessayer.');

                return $this->redirectToRoute('app_forms_anime_synopsis');
            }

            $this->addFlash('success', 'Le synopsis anime a été créé avec succès.');

            return $this->redirectToRoute('app_profile', ['_fragment' => 'summaries']);
        }

        return $this->render('forms/anime_synopsis.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/formulaires/saison', name: 'app_forms_season', methods: ['GET'])]
    public function season(): Response
    {
        return $this->render('forms/season.html.twig');
    }

    #[Route('/formulaires/scene-saison', name: 'app_forms_season_scene', methods: ['GET'])]
    public function seasonScene(): Response
    {
        return $this->render('forms/season_scene.html.twig');
    }

    #[Route('/formulaires/synopsis-manga', name: 'app_forms_manga_synopsis', methods: ['GET', 'POST'])]
    public function mangaSynopsis(
        Request $request,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $input = new MangaSynopsisInput();
        $form = $this->createForm(MangaSynopsisType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filename = null;

            try {
                $filename = $imageUploader->store($input->image);
                $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);

                $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($input, $user, $coverUrl): void {
                    $manga = (new Manga())
                        ->setTitle($input->title)
                        ->setSynopsis($input->synopsis)
                        ->setCoverMangaUrl($coverUrl)
                        ->setType('Manga')
                        ->setStatus($input->status)
                        ->setAuthor($input->author)
                        ->setMangaDate((int) $input->releaseDate->format('Y'))
                        ->setReleaseDate($input->releaseDate)
                        ->setTomeStart($input->tomeStart)
                        ->setTomeEnd($input->tomeEnd)
                        ->setChapterStart($input->chapterStart)
                        ->setChapterEnd($input->chapterEnd)
                        ->setIsPublic(false)
                        ->setOwner($user);

                    foreach ($input->categories as $category) {
                        $manga->addCategorie($category);
                    }

                    $summary = (new Summary())
                        ->setTitle($input->title)
                        ->setContent($input->synopsis)
                        ->setUser($user)
                        ->setManga($manga);

                    $entityManager->persist($manga);
                    $entityManager->persist($summary);
                });
            } catch (\Throwable) {
                if ($filename !== null) {
                    $imageUploader->remove($filename);
                }

                $this->addFlash('error', 'Le synopsis manga n’a pas pu être enregistré. Veuillez réessayer.');

                return $this->redirectToRoute('app_forms_manga_synopsis');
            }

            $this->addFlash('success', 'Le synopsis manga a été créé avec succès.');

            return $this->redirectToRoute('app_profile', ['_fragment' => 'summaries']);
        }

        return $this->render('forms/manga_synopsis.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/formulaires/miniature/{filename}',
        name: 'app_forms_synopsis_image',
        methods: ['GET'],
        requirements: ['filename' => '[a-f0-9]{32}\.(?:png|jpg)'],
    )]
    public function synopsisImage(
        string $filename,
        AnimeRepository $animeRepository,
        MangaRepository $mangaRepository,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);
        $anime = $animeRepository->findOneOwnedByCoverUrl($coverUrl, $user);
        $manga = $mangaRepository->findOneOwnedByCoverUrl($coverUrl, $user);

        if ($anime === null && $manga === null) {
            throw $this->createNotFoundException();
        }

        $path = $imageUploader->resolve($filename);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/formulaires/scene-manga', name: 'app_forms_manga_scene', methods: ['GET'])]
    public function mangaScene(): Response
    {
        return $this->render('forms/manga_scene.html.twig');
    }

    #[Route('/formulaires/personnage', name: 'app_forms_character', methods: ['GET'])]
    public function character(): Response
    {
        return $this->render('forms/character.html.twig');
    }
}
