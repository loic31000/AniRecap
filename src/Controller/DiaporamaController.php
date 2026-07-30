<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DiaporamaRepository;
use App\Repository\SlideRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DiaporamaController extends AbstractController
{
    #[Route('/diaporamas/{id}', name: 'app_diaporama_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(
        int $id,
        DiaporamaRepository $diaporamaRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $diaporama = $diaporamaRepository->findOneOwnedWithSlides($id, $user);
        if ($diaporama === null) {
            throw $this->createNotFoundException();
        }

        $slides = $diaporama->getSlides();

        return $this->render('diaporama/index.html.twig', [
            'diaporama' => $diaporama,
            'items' => $slides,
            'slide_presentations' => $this->buildSlidePresentations($slides->toArray(), $entityManager),
        ]);
    }

    /**
     * @param \App\Entity\Slide[] $slides
     *
     * @return array<int, array{
     *     workTitle: string,
     *     categories: string[],
     *     link: string|null,
     *     summary: string
     * }>
     */
    private function buildSlidePresentations(array $slides, EntityManagerInterface $entityManager): array
    {
        $animeIds = [];
        $mangaIds = [];
        $rootBySlide = [];

        foreach ($slides as $slide) {
            $anime = $slide->getEpisode()?->getSeason()?->getAnime();
            $manga = $slide->getTome()?->getManga() ?? $slide->getChapitre()?->getManga();

            if ($anime?->getId() !== null) {
                $animeIds[$anime->getId()] = $anime->getId();
                $rootBySlide[$slide->getId()] = ['type' => 'anime', 'id' => $anime->getId(), 'title' => $anime->getTitle()];
            } elseif ($manga?->getId() !== null) {
                $mangaIds[$manga->getId()] = $manga->getId();
                $rootBySlide[$slide->getId()] = ['type' => 'manga', 'id' => $manga->getId(), 'title' => $manga->getTitle()];
            }
        }

        $categoriesByRoot = [];
        if ($animeIds !== []) {
            $rows = $entityManager->createQuery(
                'SELECT anime.id AS rootId, category.name AS categoryName
                 FROM App\Entity\Anime anime
                 INNER JOIN anime.categories category
                 WHERE anime.id IN (:ids)
                 ORDER BY category.name ASC',
            )
                ->setParameter('ids', array_values($animeIds))
                ->getArrayResult();

            foreach ($rows as $row) {
                $categoriesByRoot['anime:' . $row['rootId']][] = $row['categoryName'];
            }
        }

        if ($mangaIds !== []) {
            $rows = $entityManager->createQuery(
                'SELECT manga.id AS rootId, category.name AS categoryName
                 FROM App\Entity\Manga manga
                 INNER JOIN manga.categorie category
                 WHERE manga.id IN (:ids)
                 ORDER BY category.name ASC',
            )
                ->setParameter('ids', array_values($mangaIds))
                ->getArrayResult();

            foreach ($rows as $row) {
                $categoriesByRoot['manga:' . $row['rootId']][] = $row['categoryName'];
            }
        }

        $presentations = [];
        foreach ($slides as $slide) {
            $root = $rootBySlide[$slide->getId()] ?? null;
            [$link, $summary] = $this->extractLeadingLink($slide->getContent() ?? '');
            $rootKey = $root !== null ? $root['type'] . ':' . $root['id'] : null;

            $presentations[$slide->getId()] = [
                'workTitle' => $root['title'] ?? $slide->getDiaporama()?->getTitle() ?? '',
                'categories' => $rootKey !== null ? ($categoriesByRoot[$rootKey] ?? []) : [],
                'link' => $link,
                'summary' => $summary,
            ];
        }

        return $presentations;
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function extractLeadingLink(string $content): array
    {
        $lines = preg_split('/\R/u', trim($content)) ?: [];
        $firstLine = trim($lines[0] ?? '');
        $scheme = parse_url($firstLine, PHP_URL_SCHEME);

        if (filter_var($firstLine, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
            return [null, trim($content)];
        }

        array_shift($lines);

        return [$firstLine, trim(implode("\n", $lines))];
    }

    #[Route(
        '/diaporamas/{diaporamaId}/slides/{slideId}/image',
        name: 'app_diaporama_slide_image',
        methods: ['GET'],
        requirements: ['diaporamaId' => '\d+', 'slideId' => '\d+'],
    )]
    #[IsGranted('ROLE_USER')]
    public function image(
        int $diaporamaId,
        int $slideId,
        SlideRepository $slideRepository,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $slide = $slideRepository->findOneOwnedInDiaporama($slideId, $diaporamaId, $user);
        $filename = $slide?->getImageFilename();
        if ($slide === null || $filename === null) {
            throw $this->createNotFoundException();
        }

        $path = $imageUploader->resolve($filename);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
