<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeasonController extends AbstractController
{
    #[Route('/season', name: 'app_season')]
    public function index(): Response
    {
        // On prépare le mock de données calqué sur les besoins exacts de ton Twig
        $seasonData = [
            'displayTitle' => 'Gundam Seed Destiny',
            'originalTitle' => 'ガンダムSEED デスティニー',
            'coverImage' => null, // null pour déclencher l'image par défaut (asset) du Twig
            'isFavorite' => false,
            'format' => 'Animé',
            'status' => 'En cours',
            'author' => 'Utilisateur17',
            'year' => 2005,
            'genres' => ['Action', 'Fantasy', 'Shonen'],
            
            // Synopsis découpé pour coller à la structure en paragraphes
            'synopsis' => "Dans un monde régi par des lois immuables, un jeune héros se dresse contre l'adversité pour rétablir l'équilibre. Ce voyage initiatique le mènera à travers des contrées hostiles où chaque rencontre forgera sa destinée.",
            'synopsisExtra' => "Confronté à des dilemmes moraux et à des combats d'une intensité rare, il devra puiser au plus profond de lui-même pour éveiller le pouvoir latent qui sommeille en lui.",
            'synopsisConclusion' => "Une épopée magistrale qui explore les tréfonds de l'âme humaine sous un trait clinique et acéré.",
            
            // Section Épisodes / Arc
            'arcTitle' => 'STRIKE FREEDOM',
            'arcEpisodes' => 'EP. 01 - EP. 15',
            'episodeDescription' => "L'attaque surprise des Net-Hunters déstabilise la défense locale et force Kael à fuir.",
            'episodesCountText' => '6 Épisodes disponibles',
            
            // Casting / Personnages
            'cast' => [
                [
                    'name' => 'Tsunayoshi Sawada', 
                    'role' => '澤田綱義',
                    'avatar' => null // Laissera la première lettre en fallback
                ],
                [
                    'name' => 'Uchiha Sasuke', 
                    'role' => 'うずまき・ナルト',
                    'avatar' => null
                ],
                [
                    'name' => 'Hajime No Hippo', 
                    'role' => 'ジン・モリ',
                    'avatar' => null
                ],
            ]
        ];

            return $this->render('season/index.html.twig', [
            'season' => $seasonData,
        ]);
    }
}


    