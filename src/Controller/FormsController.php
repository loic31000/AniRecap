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
use App\Repository\ChapitreRepository;
use App\Repository\EpisodeRepository;
use App\Repository\MangaRepository;
use App\Repository\SeasonRepository;
use App\Repository\TomeRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $form = $this->createForm(AnimeSynopsisType::class, $input, [
            'validation_groups' => ['Default', 'create'],
        ]);
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
            'is_edit' => false,
            'current_cover_url' => null,
        ]);
    }

    #[Route(
        '/formulaires/animes/{id}/modifier',
        name: 'app_forms_anime_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+'],
    )]
    public function editAnime(
        int $id,
        Request $request,
        AnimeRepository $animeRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $anime = $animeRepository->findOneVisibleTo($id, $user);
        if ($anime === null || $anime->getOwner() !== $user) {
            throw $this->createNotFoundException();
        }

        $input = $this->animeInputFromEntity($anime);
        $form = $this->createForm(AnimeSynopsisType::class, $input, [
            'is_edit' => true,
            'csrf_token_id' => 'anime_synopsis_edit_' . $anime->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newFilename = null;
            $oldCoverUrl = $anime->getCoverAnimeUrl();
            $coverUrl = $oldCoverUrl;

            try {
                if ($input->image instanceof UploadedFile) {
                    $newFilename = $imageUploader->store($input->image);
                    $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $newFilename]);
                }

                $entityManager->wrapInTransaction(function () use ($anime, $input, $coverUrl): void {
                    $this->applyAnimeInput($anime, $input, $coverUrl);
                });
            } catch (\Throwable) {
                if ($newFilename !== null) {
                    $imageUploader->remove($newFilename);
                }

                $this->addFlash('error', 'L’animé n’a pas pu être modifié. Veuillez réessayer.');

                return $this->redirectToRoute('app_forms_anime_edit', ['id' => $anime->getId()]);
            }

            if ($newFilename !== null && $oldCoverUrl !== null) {
                $imageUploader->remove(basename((string) parse_url($oldCoverUrl, PHP_URL_PATH)));
            }

            $this->addFlash('success', 'L’animé a été modifié avec succès.');

            return $this->redirectToRoute('app_anime_show', ['id' => $anime->getId()]);
        }

        return $this->render('forms/anime_synopsis.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'current_cover_url' => $anime->getCoverAnimeUrl(),
        ]);
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
        $form = $this->createForm(MangaSynopsisType::class, $input, [
            'validation_groups' => ['Default', 'create'],
        ]);
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
            'is_edit' => false,
            'current_cover_url' => null,
        ]);
    }

    #[Route(
        '/formulaires/mangas/{id}/modifier',
        name: 'app_forms_manga_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+'],
    )]
    public function editManga(
        int $id,
        Request $request,
        MangaRepository $mangaRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $manga = $mangaRepository->findOneVisibleTo($id, $user);
        if ($manga === null || $manga->getOwner() !== $user) {
            throw $this->createNotFoundException();
        }

        $input = $this->mangaInputFromEntity($manga);
        $form = $this->createForm(MangaSynopsisType::class, $input, [
            'is_edit' => true,
            'csrf_token_id' => 'manga_synopsis_edit_' . $manga->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newFilename = null;
            $oldCoverUrl = $manga->getCoverMangaUrl();
            $coverUrl = $oldCoverUrl;

            try {
                if ($input->image instanceof UploadedFile) {
                    $newFilename = $imageUploader->store($input->image);
                    $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $newFilename]);
                }

                $entityManager->wrapInTransaction(function () use ($manga, $input, $coverUrl): void {
                    $this->applyMangaInput($manga, $input, $coverUrl);
                });
            } catch (\Throwable) {
                if ($newFilename !== null) {
                    $imageUploader->remove($newFilename);
                }

                $this->addFlash('error', 'Le manga n’a pas pu être modifié. Veuillez réessayer.');

                return $this->redirectToRoute('app_forms_manga_edit', ['id' => $manga->getId()]);
            }

            if ($newFilename !== null && $oldCoverUrl !== null) {
                $imageUploader->remove(basename((string) parse_url($oldCoverUrl, PHP_URL_PATH)));
            }

            $this->addFlash('success', 'Le manga a été modifié avec succès.');

            return $this->redirectToRoute('app_manga_show', ['id' => $manga->getId()]);
        }

        return $this->render('forms/manga_synopsis.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'current_cover_url' => $manga->getCoverMangaUrl(),
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
        ChapitreRepository $chapitreRepository,
        EpisodeRepository $episodeRepository,
        MangaRepository $mangaRepository,
        SeasonRepository $seasonRepository,
        TomeRepository $tomeRepository,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);
        $anime = $animeRepository->findOneOwnedByCoverUrl($coverUrl, $user);
        $chapitre = $chapitreRepository->findOneOwnedByCoverUrl($coverUrl, $user);
        $episode = $episodeRepository->findOneOwnedByCoverUrl($coverUrl, $user);
        $manga = $mangaRepository->findOneOwnedByCoverUrl($coverUrl, $user);
        $season = $seasonRepository->findOneOwnedByCoverUrl($coverUrl, $user);
        $tome = $tomeRepository->findOneOwnedByCoverUrl($coverUrl, $user);

        if ($anime === null && $chapitre === null && $episode === null && $manga === null && $season === null && $tome === null) {
            throw $this->createNotFoundException();
        }

        $path = $imageUploader->resolve($filename);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function animeInputFromEntity(Anime $anime): AnimeSynopsisInput
    {
        $input = new AnimeSynopsisInput();
        $input->title = $anime->getTitle();
        $input->categories = $anime->getCategories()->toArray();
        $input->synopsis = $anime->getSynopsis();
        $input->status = $anime->getStatus();
        $input->author = $anime->getAuthor();
        $input->releaseDate = $anime->getReleaseDate();
        $input->initialSeasonNumber = $anime->getInitialSeasonNumber();
        $input->episodeCount = $anime->getEpisodeCount();

        return $input;
    }

    private function applyAnimeInput(Anime $anime, AnimeSynopsisInput $input, ?string $coverUrl): void
    {
        $anime
            ->setTitle($input->title)
            ->setSynopsis($input->synopsis)
            ->setCoverAnimeUrl($coverUrl)
            ->setStatus($input->status)
            ->setAuthor($input->author)
            ->setAnimeDate((int) $input->releaseDate->format('Y'))
            ->setReleaseDate($input->releaseDate)
            ->setInitialSeasonNumber($input->initialSeasonNumber)
            ->setEpisodeCount($input->episodeCount);

        foreach ($anime->getCategories()->toArray() as $category) {
            if (!in_array($category, $input->categories, true)) {
                $anime->removeCategorie($category);
            }
        }

        foreach ($input->categories as $category) {
            $anime->addCategorie($category);
        }
    }

    private function mangaInputFromEntity(Manga $manga): MangaSynopsisInput
    {
        $input = new MangaSynopsisInput();
        $input->title = $manga->getTitle();
        $input->categories = $manga->getCategorie()->toArray();
        $input->synopsis = $manga->getSynopsis();
        $input->status = $manga->getStatus();
        $input->author = $manga->getAuthor();
        $input->releaseDate = $manga->getReleaseDate();
        $input->tomeStart = $manga->getTomeStart();
        $input->tomeEnd = $manga->getTomeEnd();
        $input->chapterStart = $manga->getChapterStart();
        $input->chapterEnd = $manga->getChapterEnd();

        return $input;
    }

    private function applyMangaInput(Manga $manga, MangaSynopsisInput $input, ?string $coverUrl): void
    {
        $manga
            ->setTitle($input->title)
            ->setSynopsis($input->synopsis)
            ->setCoverMangaUrl($coverUrl)
            ->setStatus($input->status)
            ->setAuthor($input->author)
            ->setMangaDate((int) $input->releaseDate->format('Y'))
            ->setReleaseDate($input->releaseDate)
            ->setTomeStart($input->tomeStart)
            ->setTomeEnd($input->tomeEnd)
            ->setChapterStart($input->chapterStart)
            ->setChapterEnd($input->chapterEnd);

        foreach ($manga->getCategorie()->toArray() as $category) {
            if (!in_array($category, $input->categories, true)) {
                $manga->removeCategorie($category);
            }
        }

        foreach ($input->categories as $category) {
            $manga->addCategorie($category);
        }
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route('/formulaires/personnage', name: 'app_forms_character', methods: ['GET'])]
    public function character(): Response
    {
        return $this->render('forms/character.html.twig');
    }
}
