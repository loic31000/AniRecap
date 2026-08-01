<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\CategorieRepository;
use App\Repository\MangaRepository;
use App\Repository\FavoriteRepository;
use App\Repository\SummaryRepository;
use App\Repository\SummaryLikeRepository;
use App\Service\OeuvreFilterNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        Request $request,
        AnimeRepository $animeRepository,
        MangaRepository $mangaRepository,
        CategorieRepository $categorieRepository,
        FavoriteRepository $favoriteRepository,
        SummaryRepository $summaryRepository,
        SummaryLikeRepository $summaryLikeRepository,
        OeuvreFilterNormalizer $filterNormalizer,
    ): Response {
        $user = $this->getUser();
        $isAuthenticated = $user instanceof User;

        $genres = array_map(
            static fn ($category): array => ['slug' => $category->getSlug(), 'name' => $category->getName()],
            $categorieRepository->findBy([], ['name' => 'ASC']),
        );
        $filters = $filterNormalizer->normalize($request, $genres);

        $items = [];
        if ($filters['type'] === 'all' || $filters['type'] === 'anime') {
            $items = [
                ...$items,
                ...array_map($this->mapAnime(...), $isAuthenticated ? $animeRepository->searchVisibleTo($filters, $user) : $animeRepository->searchPublic($filters)),
            ];
        }
        if ($filters['type'] === 'all' || $filters['type'] === 'manga') {
            $items = [
                ...$items,
                ...array_map($this->mapManga(...), $isAuthenticated ? $mangaRepository->searchVisibleTo($filters, $user) : $mangaRepository->searchPublic($filters)),
            ];
        }

        if ($isAuthenticated) {
            $items = $this->applyFavoriteStates($items, $favoriteRepository, $user);
            $items = $this->applySummaryManagement($items, $summaryRepository, $user);
        }
        foreach ($items as &$item) {
            $item['canOpen'] = $isAuthenticated;
            $item['canInteract'] = $isAuthenticated;
        }
        unset($item);

        $itemsByRoot = [];
        foreach ($items as $item) {
            $itemsByRoot[$item['type'] . ':' . $item['id']] = $item;
        }
        $recentRanks = $summaryLikeRepository->findRecentlyPublishedRoots($filters, 5);
        $popularRanks = $summaryLikeRepository->findPopularRoots($filters, 5);
        $popularUsesFallback = $popularRanks === [];
        if ($popularUsesFallback) { $popularRanks = $recentRanks; }

        $popular = [];
        foreach ($popularRanks as $rank) {
            $key = $rank['type'] . ':' . $rank['id'];
            if (isset($itemsByRoot[$key])) {
                $item = $itemsByRoot[$key];
                $item['popularityLikes'] = $rank['score'];
                $popular[] = $item;
            }
        }

        $available = [];
        foreach ($recentRanks as $rank) {
            $key = $rank['type'] . ':' . $rank['id'];
            if (isset($itemsByRoot[$key])) {
                $item = $itemsByRoot[$key];
                $item['popularityLikes'] = $rank['score'];
                $available[] = $item;
            }
        }

        return $this->render('home/index.html.twig', [
            'popular' => $popular,
            'available' => $available,
            'genres' => $genres,
            'filters' => $filters,
            'is_authenticated' => $isAuthenticated,
            'popular_uses_fallback' => $popularUsesFallback,
        ]);
    }

    private function mapAnime(Anime $anime): array
    {
        return [
            'id' => $anime->getId(),
            'type' => 'anime',
            'title' => $anime->getTitle(),
            'subtitle' => $anime->getStatus() ?: 'Statut non renseigné',
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

    private function applyFavoriteStates(array $items, FavoriteRepository $repository, User $user): array
    {
        $states = $repository->findRootFavoriteStates(
            $user,
            array_column(array_filter($items, static fn (array $item): bool => $item['type'] === 'anime'), 'id'),
            array_column(array_filter($items, static fn (array $item): bool => $item['type'] === 'manga'), 'id'),
        );
        foreach ($items as &$item) {
            $item['isFavorite'] = $states[$item['type']][$item['id']] ?? false;
        }
        unset($item);

        return $items;
    }

    private function applySummaryManagement(array $items, SummaryRepository $repository, User $user): array
    {
        $states = $repository->findRootManagementStates(
            $user,
            array_column(array_filter($items, static fn (array $item): bool => $item['type'] === 'anime'), 'id'),
            array_column(array_filter($items, static fn (array $item): bool => $item['type'] === 'manga'), 'id'),
        );
        foreach ($items as &$item) {
            $item['summaryManagement'] = $states[$item['type']][$item['id']] ?? null;
        }
        unset($item);

        return $items;
    }
}
