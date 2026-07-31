<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\MangaRepository;
use App\Repository\CategorieRepository;
use App\Repository\FavoriteRepository;
use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\User;
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
        CategorieRepository $categorieRepository,
        FavoriteRepository $favoriteRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

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

        if ($filters['type'] === 'all' || $filters['type'] === 'anime') {
            foreach ($animeRepository->searchVisibleTo($filters, $user) as $anime) {
                $catalogueItems[] = $this->mapAnime($anime);
            }
        }

        if ($filters['type'] === 'all' || $filters['type'] === 'manga') {
            foreach ($mangaRepository->searchVisibleTo($filters, $user) as $manga) {
                $catalogueItems[] = $this->mapManga($manga);
            }
        }

        $states = $favoriteRepository->findRootFavoriteStates(
            $user,
            array_column(array_filter($catalogueItems, static fn (array $item): bool => $item['type'] === 'anime'), 'id'),
            array_column(array_filter($catalogueItems, static fn (array $item): bool => $item['type'] === 'manga'), 'id'),
        );
        foreach ($catalogueItems as &$item) {
            $item['isFavorite'] = $states[$item['type']][$item['id']] ?? false;
        }
        unset($item);

        return $this->render('catalogue/index.html.twig', [
            'results' => $catalogueItems,
            'genres' => $genreOptions,
            'filters' => $filters,
        ]);
    }

    private function mapAnime(Anime $anime): array
    {
        return [
            'id' => $anime->getId(),
            'type' => 'anime',
            'title' => $anime->getTitle(),
            'subtitle' => sprintf('%d saison(s) • %s', $anime->getSeasons()->count(), $anime->getStatus() ?: 'Statut non renseigné'),
            'author' => $anime->getAuthor(),
            'date' => (string) ($anime->getAnimeDate() ?? ''),
            'cover' => $anime->getCoverAnimeUrl(),
            'votesCount' => $anime->getVotes()->count(),
            'isPrivate' => !$anime->isPublic(),
        ];
    }

    private function mapManga(Manga $manga): array
    {
        return [
            'id' => $manga->getId(),
            'type' => 'manga',
            'title' => $manga->getTitle(),
            'subtitle' => $manga->getStatus() ?: 'Statut non renseigné',
            'author' => $manga->getAuthor(),
            'date' => (string) ($manga->getMangaDate() ?? ''),
            'cover' => $manga->getCoverMangaUrl(),
            'votesCount' => $manga->getVotes()->count(),
            'isPrivate' => !$manga->isPublic(),
        ];
    }
}
