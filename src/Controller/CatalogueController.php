<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\MangaRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogueController extends AbstractController
{
    #[Route('/catalogue', name: 'app_catalogue', methods: ['GET'])]
    public function index(
        Request $request,
        AnimeRepository $animeRepository,
        MangaRepository $mangaRepository,
        CategorieRepository $categorieRepository
    ): Response {
        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'type' => (string) $request->query->get('type', 'all'),
            'genre' => $request->query->get('genre') ?: null,
            'annee' => $request->query->get('annee') ?: null,
        ];

        $genreOptions = [
            ['slug' => 'action-adventure', 'name' => 'Action / Aventure'],
            ['slug' => 'romance', 'name' => 'Romance'],
            ['slug' => 'comedie', 'name' => 'Comédie'],
            ['slug' => 'drama', 'name' => 'Drame'],
            ['slug' => 'fantasy', 'name' => 'Fantasy'],
            ['slug' => 'science-fiction', 'name' => 'Science-Fiction'],
            ['slug' => 'thriller-mystere', 'name' => 'Thriller / Mystère'],
            ['slug' => 'tranche-de-vie', 'name' => 'Tranche de vie'],
        ];

        $catalogueItems = [
            [
                'type' => 'anime',
                'title' => 'Demon Slayer',
                'subtitle' => 'Saison 3 • 24 épisodes',
                'author' => 'Koyoharu Gotouge',
                'categories' => ['action-adventure', 'fantasy'],
                'date' => '2024',
                'votes' => 843,
                'gradient' => 'linear-gradient(135deg, #274b7a, #6fa8dc)',
                'favorited' => true,
            ],
            [
                'type' => 'manga',
                'title' => 'One Piece',
                'subtitle' => 'Chapitre 1200+ • Eiichiro Oda',
                'author' => 'Eiichiro Oda',
                'categories' => ['action-adventure'],
                'date' => '2025',
                'votes' => 1250,
                'gradient' => 'linear-gradient(135deg, #ff8a3d, #ffcf5c)',
                'favorited' => false,
            ],
            [
                'type' => 'anime',
                'title' => 'Attack on Titan',
                'subtitle' => 'Final season • Épisode 28',
                'author' => 'Hajime Isayama',
                'categories' => ['action-adventure', 'drama'],
                'date' => '2023',
                'votes' => 980,
                'gradient' => 'linear-gradient(135deg, #111111, #3a3a3a)',
                'favorited' => true,
            ],
            [
                'type' => 'manga',
                'title' => 'Tokyo Ghoul',
                'subtitle' => 'Chapitre 200 • Classics',
                'author' => 'Sui Ishida',
                'categories' => ['thriller-mystere', 'drama'],
                'date' => '2022',
                'votes' => 432,
                'gradient' => 'linear-gradient(135deg, #4b2e3e, #a83f5e)',
                'favorited' => false,
            ],
            [
                'type' => 'anime',
                'title' => 'My Hero Academia',
                'subtitle' => 'Saison 6 • Nouveaux héros',
                'author' => 'Kohei Horikoshi',
                'categories' => ['action-adventure', 'comedie'],
                'date' => '2025',
                'votes' => 1090,
                'gradient' => 'linear-gradient(135deg, #2d3d5b, #7ca6f8)',
                'favorited' => false,
            ],
        ];

        $viewResults = array_filter($catalogueItems, function (array $item) use ($filters): bool {
            if ($filters['type'] !== 'all' && $item['type'] !== $filters['type']) {
                return false;
            }

            if (!empty($filters['genre']) && !in_array($filters['genre'], $item['categories'], true)) {
                return false;
            }

            if (!empty($filters['annee']) && $item['date'] !== (string) $filters['annee']) {
                return false;
            }

            if (!empty($filters['q'])) {
                $search = mb_strtolower($filters['q']);
                $matchesTitle = mb_stripos($item['title'], $search) !== false;
                $matchesAuthor = mb_stripos($item['author'], $search) !== false;
                return $matchesTitle || $matchesAuthor;
            }

            return true;
        });

        $viewResults = array_values($viewResults);

        return $this->render('catalogue/index.html.twig', [
            'controller_name' => 'CatalogueController',
            'results' => $viewResults,
            'genres' => $genreOptions,
            'filters' => $filters,
        ]);
    }
}