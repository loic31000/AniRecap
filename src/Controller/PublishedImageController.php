<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\ChapitreRepository;
use App\Repository\EpisodeRepository;
use App\Repository\MangaRepository;
use App\Repository\SeasonRepository;
use App\Repository\SummaryRepository;
use App\Repository\TomeRepository;
use App\Service\SynopsisImageUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class PublishedImageController extends AbstractController
{
    #[Route('/formulaires/miniature/{filename}', name: 'app_forms_synopsis_image', methods: ['GET'], requirements: ['filename' => '[a-f0-9]{32}\.(?:png|jpg)'])]
    public function show(
        string $filename,
        AnimeRepository $animeRepository,
        ChapitreRepository $chapitreRepository,
        EpisodeRepository $episodeRepository,
        MangaRepository $mangaRepository,
        SeasonRepository $seasonRepository,
        TomeRepository $tomeRepository,
        SummaryRepository $summaryRepository,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);
        $user = $this->getUser();
        $owned = $user instanceof User && (
            $animeRepository->findOneOwnedByCoverUrl($coverUrl, $user) !== null
            || $mangaRepository->findOneOwnedByCoverUrl($coverUrl, $user) !== null
            || $seasonRepository->findOneOwnedByCoverUrl($coverUrl, $user) !== null
            || $episodeRepository->findOneOwnedByCoverUrl($coverUrl, $user) !== null
            || $tomeRepository->findOneOwnedByCoverUrl($coverUrl, $user) !== null
            || $chapitreRepository->findOneOwnedByCoverUrl($coverUrl, $user) !== null
        );
        $public = $animeRepository->findOneBy(['coverAnimeUrl' => $coverUrl, 'isPublic' => true]) !== null
            || $mangaRepository->findOneBy(['coverMangaUrl' => $coverUrl, 'isPublic' => true]) !== null
            || $summaryRepository->isPublicChildCoverVisible($coverUrl);

        if (!$owned && !$public) {
            throw $this->createNotFoundException();
        }
        $path = $imageUploader->resolve($filename);
        if ($path === null) { throw $this->createNotFoundException(); }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
