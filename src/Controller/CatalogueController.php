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

        $genreOptions = array_values(array_map(
            static fn ($categorie) => ['slug' => $categorie->getSlug(), 'name' => $categorie->getName()],
            $categorieRepository->findAll()
        ));

        if ($genreOptions === []) {
            $genreOptions = [
                ['slug' => 'action-adventure', 'name' => 'Action / Aventure'],
                ['slug' => 'romance', 'name' => 'Romance'],
            ];
        }

        $catalogueItems = [];

        foreach ($animeRepository->findPublic() as $anime) {
            $catalogueItems[] = [
                'type' => 'anime',
                'title' => $anime->getTitle(),
                'subtitle' => sprintf('Saison %d • %s', $anime->getSeasons()->count() ?: 1, $anime->getStatus() ?: 'À venir'),
                'author' => $anime->getAuthor(),
                'categories' => array_map(static fn ($categorie) => $categorie->getSlug(), iterator_to_array($anime->getCategories())),
                'date' => (string) ($anime->getAnimeDate() ?? ''),
                'votes' => 500 + ($anime->getId() ?? 0) * 50,
                'gradient' => 'linear-gradient(135deg, #274b7a, #6fa8dc)',
                'favorited' => false,
            ];
        }

        foreach ($mangaRepository->findPublic() as $manga) {
            $catalogueItems[] = [
                'type' => 'manga',
                'title' => $manga->getTitle(),
                'subtitle' => sprintf('Chapitre %s • %s', $manga->getMangaDate() ?? '1', $manga->getAuthor()),
                'author' => $manga->getAuthor(),
                'categories' => array_map(static fn ($categorie) => $categorie->getSlug(), iterator_to_array($manga->getCategorie())),
                'date' => (string) ($manga->getMangaDate() ?? ''),
                'votes' => 600 + ($manga->getId() ?? 0) * 40,
                'gradient' => 'linear-gradient(135deg, #ff8a3d, #ffcf5c)',
                'favorited' => true,
            ];
        }

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
