<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AnimeController extends AbstractController
{
    #[Route('/anime/{id}', name: 'app_anime_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, AnimeRepository $animeRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $anime = $animeRepository->findOneVisibleTo($id, $user);
        if ($anime === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('anime/index.html.twig', [
            'anime' => $anime,
            'is_owner' => $anime->getOwner()?->getId() === $user->getId(),
        ]);
    }
}
