<?php

namespace App\Controller;

use App\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CharacterController extends AbstractController
{
    #[Route('/character', name: 'app_character_index')]
    public function index(CharacterRepository $characterRepository): Response
    {
        $characters = $characterRepository->findAll();

        return $this->render('character/index.html.twig', [
            'characters' => $characters,
        ]);
    }

    #[Route('/character/{id}', name: 'app_character_show')]
    public function show(int $id, CharacterRepository $characterRepository): Response
    {
        $character = $characterRepository->find($id);

        if (!$character) {
            throw $this->createNotFoundException('Personnage introuvable.');
        }

        return $this->render('character/index.html.twig', [
            'character'         => $character,
            'workType'          => $character->getAnimes()->count() > 0 ? 'アニメ' : 'マンガ',
            'workTitle'         => $character->getAnimes()->first()?->getTitle()
                                    ?? $character->getMangas()->first()?->getTitle(),
            'relatedCharacters' => array_filter(
                $characterRepository->findAll(),
                static fn ($related) => $related->getId() !== $character->getId()
            ),
            'catalogueRoute'    => 'app_catalogue',
        ]);
    }
}