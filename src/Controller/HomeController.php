<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\CategorieRepository;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        Request $request,
        AnimeRepository $animeRepository,
        MangaRepository $mangaRepository,
        CategorieRepository $categorieRepository,
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

        $items = [];
        if ($filters['type'] === 'all' || $filters['type'] === 'anime') {
            $items = [
                ...$items,
                ...array_map($this->mapAnime(...), $animeRepository->searchVisibleTo($filters, $user)),
            ];
        }
        if ($filters['type'] === 'all' || $filters['type'] === 'manga') {
            $items = [
                ...$items,
                ...array_map($this->mapManga(...), $mangaRepository->searchVisibleTo($filters, $user)),
            ];
        }

        $popular = $items;
        usort($popular, static fn (array $left, array $right): int =>
            [$right['votesCount'], $right['id'], $right['type']]
            <=> [$left['votesCount'], $left['id'], $left['type']]
        );

        $latest = $items;
        usort($latest, static fn (array $left, array $right): int =>
            [$right['id'], $right['type']] <=> [$left['id'], $left['type']]
        );

        $genres = array_map(
            static fn ($category): array => [
                'slug' => $category->getSlug(),
                'name' => $category->getName(),
            ],
            $categorieRepository->findBy([], ['name' => 'ASC']),
        );

        return $this->render('home/index.html.twig', [
            'popular' => array_slice($popular, 0, 5),
            'latest' => array_slice($latest, 0, 4),
            'genres' => $genres,
            'filters' => $filters,
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
}
