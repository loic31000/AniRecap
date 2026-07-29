<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class FormsController extends AbstractController
{
    #[Route('/formulaires', name: 'app_forms_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('forms/index.html.twig');
    }

    #[Route('/formulaires/synopsis-anime', name: 'app_forms_anime_synopsis', methods: ['GET'])]
    public function animeSynopsis(): Response
    {
        return $this->render('forms/anime_synopsis.html.twig');
    }

    #[Route('/formulaires/saison', name: 'app_forms_season', methods: ['GET'])]
    public function season(): Response
    {
        return $this->render('forms/season.html.twig');
    }

    #[Route('/formulaires/scene-saison', name: 'app_forms_season_scene', methods: ['GET'])]
    public function seasonScene(): Response
    {
        return $this->render('forms/season_scene.html.twig');
    }

    #[Route('/formulaires/synopsis-manga', name: 'app_forms_manga_synopsis', methods: ['GET'])]
    public function mangaSynopsis(): Response
    {
        return $this->render('forms/manga_synopsis.html.twig');
    }

    #[Route('/formulaires/scene-manga', name: 'app_forms_manga_scene', methods: ['GET'])]
    public function mangaScene(): Response
    {
        return $this->render('forms/manga_scene.html.twig');
    }

    #[Route('/formulaires/personnage', name: 'app_forms_character', methods: ['GET'])]
    public function character(): Response
    {
        return $this->render('forms/character.html.twig');
    }
}
