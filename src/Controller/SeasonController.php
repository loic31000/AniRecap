<?php

namespace App\Controller;

use App\Repository\SeasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeasonController extends AbstractController
{
    #[Route('/season', name: 'app_season', methods: ['GET'])]
    public function index(SeasonRepository $seasonRepository): Response
    {
        $seasonEntity = $seasonRepository->findFirstPublic();

        $seasonData = [
            'displayTitle' => $seasonEntity?->getTitle() ?? 'Gundam Seed Destiny',
            'originalTitle' => $seasonEntity?->getTitle() ?? 'ガンダムSEED デスティニー',
            'coverImage' => $seasonEntity?->getCoverSeasonUrl() ?: '/images/coverAnime.png',
            'isFavorite' => false,
            'format' => $seasonEntity?->getType() ?? 'Animé',
            'status' => $seasonEntity?->getStatus() ?? 'En cours',
            'author' => $seasonEntity?->getAuthor() ?? 'Utilisateur17',
            'year' => $seasonEntity?->getSeasonDate() ?? 2005,
            'genres' => array_map(static fn ($categorie) => $categorie->getName(), iterator_to_array($seasonEntity?->getCategorie() ?? [])),
            'synopsis' => $seasonEntity?->getSynopsis() ?? 'Dans un monde régi par des lois immuables, un jeune héros se dresse contre l’adversité pour rétablir l’équilibre.',
            'synopsisExtra' => 'Confronté à des dilemmes moraux, il devra puiser au plus profond de lui-même.',
            'synopsisConclusion' => 'Une épopée magistrale qui explore les tréfonds de l’âme humaine.',
            'arcTitle' => 'STRIKE FREEDOM',
            'arcRange' => 'EP. 01 - EP. 15',
            'episodeDescription' => 'L’attaque surprise des forces adverses bouleverse la défense locale.',
            'episodesCountText' => '6 Épisodes disponibles',
            'cast' => [
                ['name' => $seasonEntity?->getAuthor() ?? 'Auteur', 'role' => 'Créateur', 'avatar' => null],
            ],
        ];

        return $this->render('season/index.html.twig', [
            'season' => $seasonData,
        ]);
    }
}
