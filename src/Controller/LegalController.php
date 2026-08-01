<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/politique-de-confidentialite', name: 'app_privacy_policy', methods: ['GET'])]
    public function privacyPolicy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/accessibilite', name: 'app_accessibility', methods: ['GET'])]
    public function accessibility(): Response
    {
        return $this->render('legal/accessibility.html.twig');
    }
}
