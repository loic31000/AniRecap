<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(AnimeRepository $animeRepository, MangaRepository $mangaRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $items = [
            ...array_map($this->mapAnime(...), $animeRepository->findVisibleTo($user)),
            ...array_map($this->mapManga(...), $mangaRepository->findVisibleTo($user)),
        ];

        $popular = $items;
        usort($popular, static fn (array $left, array $right): int =>
            [$right['votesCount'], $right['id'], $right['type']]
            <=> [$left['votesCount'], $left['id'], $left['type']]
        );

        $latest = $items;
        usort($latest, static fn (array $left, array $right): int =>
            [$right['id'], $right['type']] <=> [$left['id'], $left['type']]
        );

        return $this->render('home/index.html.twig', [
            'popular' => $popular,
            'latest' => $latest,
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
