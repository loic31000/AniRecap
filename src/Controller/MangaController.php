<?php

namespace App\Controller;

use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MangaController extends AbstractController
{
    #[Route('/manga', name: 'app_manga')]
    public function index(MangaRepository $mangaRepository): Response
    {
        $manga = $mangaRepository->findOnePublic();

        $mangaData = [
            'displayTitle' => $manga?->getTitle() ?? 'Dragon Ball Z',
            'originalTitle' => $manga?->getTitle() ?? 'ドラゴンボール',
            'coverImage' => $manga?->getCoverMangaUrl(),
            'isFavorite' => true,
            'format' => $manga?->getType() ?? 'Manga',
            'status' => $manga?->getStatus() ?? 'Terminé',
            'author' => $manga?->getAuthor() ?? 'Utilisateur17',
            'year' => $manga?->getMangaDate() ?? 1984,
            'genres' => array_map(static fn ($categorie) => $categorie->getName(), iterator_to_array($manga?->getCategorie() ?? [])),
            'synopsis' => $manga?->getSynopsis() ?? 'Dans un monde régi par des lois immuables, un jeune héros se dresse contre l’adversité pour rétablir l’équilibre.',
            'synopsisExtra' => 'Une aventure riche en émotions et en combats.',
            'activeTab' => 'chapters',
            'chaptersCountText' => '6 Chapitres disponibles',
            'chapters' => [
                ['range' => 'CHAP. 01 - CHAP. 15', 'title' => 'Arc de départ', 'description' => 'Les premiers pas de l’héroïne.', 'image' => $manga?->getCoverMangaUrl() ?? '/images/coverCardManga.png', 'spoiler' => null],
                ['range' => 'CHAP. 16 - CHAP. 45', 'title' => 'Arc des révélations', 'description' => 'La vérité éclaire le destin.', 'image' => $manga?->getCoverMangaUrl() ?? '/images/coverCardManga.png', 'spoiler' => null],
            ],
            'cast' => [
                ['name' => $manga?->getAuthor() ?? 'Auteur', 'role' => 'Créateur'],
            ],
        ];

        return $this->render('manga/index.html.twig', [
            'manga' => $mangaData,
        ]);
    }
}
