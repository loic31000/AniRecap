<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MangaController extends AbstractController
{
    #[Route('/manga/{id}', name: 'app_manga_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, MangaRepository $mangaRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $manga = $mangaRepository->findOneVisibleTo($id, $user);
        if ($manga === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('manga/index.html.twig', [
            'manga' => $manga,
        ]);
    }
}
