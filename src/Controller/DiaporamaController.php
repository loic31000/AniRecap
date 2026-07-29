<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DiaporamaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DiaporamaController extends AbstractController
{
    #[Route('/diaporama', name: 'app_diaporama', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(DiaporamaRepository $diaporamaRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $diaporamas = $diaporamaRepository->findAllWithRelationsByUser($user);

        return $this->render('diaporama/index.html.twig', [
            'items' => $diaporamas,
        ]);
    }
}
