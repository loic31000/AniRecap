<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Favorite;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\CategorieRepository;
use App\Repository\FavoriteRepository;
use App\Repository\MangaRepository;
use App\Repository\SummaryRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class FavoriteController extends AbstractController
{
    #[Route('/favoris/{type}/{id}/ajouter', name: 'app_favorite_add', methods: ['POST'], requirements: ['type' => 'anime|manga', 'id' => '\\d+'])]
    public function add(
        string $type,
        int $id,
        Request $request,
        FavoriteRepository $favoriteRepository,
        AnimeRepository $animeRepository,
        MangaRepository $mangaRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->requireUser();
        if (!$this->isCsrfTokenValid(sprintf('favorite_add_%s_%d', $type, $id), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $work = $type === 'anime'
            ? $animeRepository->findOneVisibleTo($id, $user)
            : $mangaRepository->findOneVisibleTo($id, $user);
        if ($work === null) {
            throw $this->createNotFoundException('Œuvre introuvable.');
        }

        if (!$favoriteRepository->rootFavoriteExists($user, $type, $id)) {
            $favorite = (new Favorite())->setUser($user)->setCreatedAt(new \DateTime());
            $type === 'anime' ? $favorite->setAnime($work) : $favorite->setManga($work);
            try {
                $entityManager->persist($favorite);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                // Une requête concurrente a déjà créé le même favori : succès idempotent.
            }
        }

        $this->addFlash('success', sprintf('« %s » a été ajouté à vos favoris.', $work->getTitle()));

        return $this->redirectSafely($request);
    }

    #[Route('/favoris/{type}/{id}/retirer', name: 'app_favorite_remove', methods: ['POST'], requirements: ['type' => 'anime|manga', 'id' => '\\d+'])]
    public function remove(
        string $type,
        int $id,
        Request $request,
        FavoriteRepository $favoriteRepository,
        AnimeRepository $animeRepository,
        MangaRepository $mangaRepository,
    ): Response {
        $user = $this->requireUser();
        if (!$this->isCsrfTokenValid(sprintf('favorite_remove_%s_%d', $type, $id), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $work = $type === 'anime'
            ? $animeRepository->findOneVisibleTo($id, $user)
            : $mangaRepository->findOneVisibleTo($id, $user);
        if ($work === null) {
            throw $this->createNotFoundException('Œuvre introuvable.');
        }

        $favoriteRepository->removeRootFavorites($user, $type, $id);
        $this->addFlash('success', sprintf('« %s » a été retiré de vos favoris.', $work->getTitle()));

        return $this->redirectSafely($request);
    }

    #[Route('/favoris', name: 'app_favorites', methods: ['GET'])]
    public function index(
        Request $request,
        FavoriteRepository $favoriteRepository,
        CategorieRepository $categorieRepository,
        SummaryRepository $summaryRepository,
    ): Response {
        $user = $this->requireUser();

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

        $states = $favoriteRepository->findRootFavoriteStates(
            $user,
            array_column(array_filter($cards, static fn (array $card): bool => $card['type'] === 'anime'), 'id'),
            array_column(array_filter($cards, static fn (array $card): bool => $card['type'] === 'manga'), 'id'),
        );
        foreach ($cards as &$card) {
            $card['isFavorite'] = $states[$card['type']][$card['id']] ?? false;
        }
        unset($card);

        $summaryStates = $summaryRepository->findRootManagementStates(
            $user,
            array_column(array_filter($cards, static fn (array $card): bool => $card['type'] === 'anime'), 'id'),
            array_column(array_filter($cards, static fn (array $card): bool => $card['type'] === 'manga'), 'id'),
        );
        foreach ($cards as &$card) {
            $card['summaryManagement'] = $summaryStates[$card['type']][$card['id']] ?? null;
        }
        unset($card);

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
                'isFavorite' => false,
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
            'isFavorite' => false,
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

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function redirectSafely(Request $request): Response
    {
        $target = (string) $request->request->get('return_to', '');
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return $this->redirectToRoute('app_home');
        }

        $parts = parse_url($target);
        if ($parts === false || isset($parts['scheme'], $parts['host'])) {
            return $this->redirectToRoute('app_home');
        }

        return $this->redirect($target);
    }
}
