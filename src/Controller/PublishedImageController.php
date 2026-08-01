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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class PublishedImageController extends AbstractController
{
    #[Route('/formulaires/miniature/{filename}', name: 'app_forms_synopsis_image', methods: ['GET'], requirements: ['filename' => '[a-f0-9]{32}\.(?:png|jpg)'])]
    public function show(
        Request $request,
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
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        if ($request->query->getString('variant') === 'card') {
            $path = $this->createCardThumbnail($path, $filename);
        }

        $response = $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
        $response->setPrivate();
        $response->setMaxAge(604800);

        return $response;
    }

    private function createCardThumbnail(string $sourcePath, string $filename): string
    {
        $cacheDirectory = $this->getParameter('kernel.cache_dir').'/card_thumbnails';
        $thumbnailPath = $cacheDirectory.'/'.pathinfo($filename, PATHINFO_FILENAME).'.webp';

        if (is_file($thumbnailPath) && filemtime($thumbnailPath) >= filemtime($sourcePath)) {
            return $thumbnailPath;
        }

        if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0700, true) && !is_dir($cacheDirectory)) {
            return $sourcePath;
        }

        $imageData = file_get_contents($sourcePath);
        $source = $imageData === false ? false : imagecreatefromstring($imageData);
        if ($source === false) {
            return $sourcePath;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = 800;
        $targetHeight = 350;
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $sourceX = (int) floor(($sourceWidth - $cropWidth) / 2);
            $sourceY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $sourceX = 0;
            $sourceY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($thumbnail === false) {
            imagedestroy($source);

            return $sourcePath;
        }

        imagecopyresampled(
            $thumbnail,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight,
        );

        $created = imagewebp($thumbnail, $thumbnailPath, 75);
        imagedestroy($thumbnail);
        imagedestroy($source);

        return $created ? $thumbnailPath : $sourcePath;
    }
}
