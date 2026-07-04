<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnimeController extends AbstractController
{
    #[Route('/anime', name: 'app_anime')]
    public function index(): Response
    {
        $anime = [
            'coverImage' => '/images/coverAnime.png',
            'title' => 'ガンダムSEED  Solo Leveling',
            'subtitle' => 'Arise from the Shadow',
            'format' => 'TV',
            'status' => 'En cours',
            'animeLabel' => 'Printemps 2026',
            'episodesCount' => 12,
            'studio' => 'A-1 Pictures',
            'duration' => '24 min',
            'averageScore' => 8.7,
            'reviewsCount' => 1248,
            'synopsis' => 'Kira Yamato est un jeune étudiant sur la colonie neutre Heliopolis, lorsque celle-ci est prise d’assaut par ZAFT, les forces armées des PLANT qui cherchent à s’emparer des nouveaux prototypes de Mobile Suit des forces de l’Alliance terrestre, les Strike, Aegis, Buster, Blitz et Duel. Commence pour Kira et ses amis, embarqués à leur corps défendant sur le croiseur terrien Archangel, une fuite éperdue à travers l’espace, puis sur Terre à travers le globe, fuyant les forces de ZAFT. ',
            'platforms' => ['Crunchyroll', 'Netflix'],
            'followers' => 18240,
            'watchlistCount' => 6211,
            'genres' => ['Action', 'Fantasy'],
            'rating' => '16+',
            'episodes' => [],
            'cast' => [],
            'diaporama' => [],
        ];

        return $this->render('anime/index.html.twig', [
            'anime' => $anime,
        ]);
    }
}