<?php

namespace App\Controller;

use App\Repository\DiaporamaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DiaporamaController extends AbstractController
{
    #[Route('/diaporama', name: 'app_diaporama')]
    public function index(DiaporamaRepository $diaporamaRepository): Response
    {
        // findAllWithRelations() : methode custom a ajouter a ton repository
        // (voir DiaporamaRepository_addition.php) pour eviter le N+1 sur
        // episode -> season -> anime -> categories.
        $diaporamas = $diaporamaRepository->findAllWithRelations();

        return $this->render('diaporama/index.html.twig', [
            'items' => $diaporamas,
        ]);
    }
}