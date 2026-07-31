<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Favorite;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\CategorieRepository;
use App\Repository\FavoriteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class FavoriteController extends AbstractController
{
    #[Route('/favoris', name: 'app_favorites', methods: ['GET'])]
    public function index(
        Request $request,
        FavoriteRepository $favoriteRepository,
        CategorieRepository $categorieRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'type' => (string) $request->query->get('type', 'all'),
            'genre' => (string) $request->query->get('genre', ''),
            'annee' => (string) $request->query->get('annee', ''),
        ];

        $cards = [];
        foreach ($favoriteRepository->findByUser($user) as $favorite) {
            $card = $this->buildCard($favorite);
            if ($card === null) {
                continue;
            }

            if (isset($cards[$card['type'] . ':' . $card['id']])) {
                continue;
            }

            if ($this->matchesFilters($card, $filters)) {
                $cards[$card['type'] . ':' . $card['id']] = $card;
            }
        }

        $availableGenres = array_map(static fn ($category): array => [
            'slug' => $category->getSlug(),
            'name' => $category->getName(),
        ], $categorieRepository->findAllAlphabetically());

        return $this->render('favorite/index.html.twig', [
            'favorites' => array_values($cards),
            'genres' => $availableGenres,
            'filters' => $filters,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildCard(Favorite $favorite): ?array
    {
        $anime = $favorite->getAnime()
            ?? $favorite->getSeason()?->getAnime()
            ?? $favorite->getEpisode()?->getSeason()?->getAnime();
        if ($anime instanceof Anime) {
            $categories = $anime->getCategories()->toArray();

            return [
                'id' => $anime->getId(),
                'type' => 'anime',
                'title' => $anime->getTitle(),
                'subtitle' => $this->animeProgress($anime),
                'date' => $favorite->getCreatedAt()?->format('d.m.Y'),
                'dateLabel' => 'Ajouté le',
                'year' => $anime->getAnimeDate(),
                'cover' => $anime->getCoverAnimeUrl(),
                'votesCount' => $anime->getVotes()->count(),
                'isPrivate' => !$anime->isPublic(),
                'isFavorite' => true,
                'category' => $categories[0]->getName() ?? null,
                'categorySlugs' => array_map(static fn ($category): ?string => $category->getSlug(), $categories),
                'categories' => array_map(static fn ($category): array => [
                    'slug' => $category->getSlug(),
                    'name' => $category->getName(),
                ], $categories),
            ];
        }

        $manga = $favorite->getManga()
            ?? $favorite->getTome()?->getManga()
            ?? $favorite->getChapitre()?->getManga();
        if (!$manga instanceof Manga) {
            return null;
        }

        $categories = $manga->getCategorie()->toArray();

        return [
            'id' => $manga->getId(),
            'type' => 'manga',
            'title' => $manga->getTitle(),
            'subtitle' => $this->mangaProgress($manga),
            'date' => $favorite->getCreatedAt()?->format('d.m.Y'),
            'dateLabel' => 'Ajouté le',
            'year' => $manga->getMangaDate(),
            'cover' => $manga->getCoverMangaUrl(),
            'votesCount' => $manga->getVotes()->count(),
            'isPrivate' => !$manga->isPublic(),
            'isFavorite' => true,
            'category' => $categories[0]->getName() ?? null,
            'categorySlugs' => array_map(static fn ($category): ?string => $category->getSlug(), $categories),
            'categories' => array_map(static fn ($category): array => [
                'slug' => $category->getSlug(),
                'name' => $category->getName(),
            ], $categories),
        ];
    }

    private function animeProgress(Anime $anime): string
    {
        $seasons = $anime->getSeasons()->toArray();
        if ($seasons === []) {
            return $anime->getStatus() ?: 'Aucune saison renseignée';
        }

        usort($seasons, static fn ($left, $right): int => $right->getNumber() <=> $left->getNumber());
        $season = $seasons[0];
        $numbers = array_map(static fn ($episode): ?int => $episode->getNumber(), $season->getEpisodes()->toArray());
        $numbers = array_values(array_filter($numbers, static fn (?int $number): bool => $number !== null));
        sort($numbers);

        return $numbers === []
            ? sprintf('Saison %d', $season->getNumber())
            : sprintf('Saison %d · EP %d – EP %d', $season->getNumber(), $numbers[0], $numbers[array_key_last($numbers)]);
    }

    private function mangaProgress(Manga $manga): string
    {
        $chapterNumbers = array_map(static fn ($chapter): ?int => $chapter->getNumber(), $manga->getChapitres()->toArray());
        $chapterNumbers = array_values(array_filter($chapterNumbers, static fn (?int $number): bool => $number !== null));
        sort($chapterNumbers);
        if ($chapterNumbers !== []) {
            return sprintf('Chapitre %d – Chapitre %d', $chapterNumbers[0], $chapterNumbers[array_key_last($chapterNumbers)]);
        }

        $tomeNumbers = array_map(static fn ($tome): ?int => $tome->getNumber(), $manga->getTomes()->toArray());
        $tomeNumbers = array_values(array_filter($tomeNumbers, static fn (?int $number): bool => $number !== null));
        sort($tomeNumbers);

        return $tomeNumbers === []
            ? ($manga->getStatus() ?: 'Aucun tome renseigné')
            : sprintf('Tome %d – Tome %d', $tomeNumbers[0], $tomeNumbers[array_key_last($tomeNumbers)]);
    }

    /**
     * @param array<string, mixed>  $card
     * @param array<string, string> $filters
     */
    private function matchesFilters(array $card, array $filters): bool
    {
        if ($filters['type'] !== 'all' && $card['type'] !== $filters['type']) {
            return false;
        }

        if ($filters['q'] !== '' && !str_contains(
            mb_strtolower($card['title'] . ' ' . $card['subtitle']),
            mb_strtolower($filters['q']),
        )) {
            return false;
        }

        if ($filters['genre'] !== '' && !in_array($filters['genre'], $card['categorySlugs'], true)) {
            return false;
        }

        return $filters['annee'] === '' || (string) $card['year'] === $filters['annee'];
    }
}
