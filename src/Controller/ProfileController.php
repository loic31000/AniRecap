<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $current = $this->getUser();

        if ($current instanceof User) {
            $avatar = $current->getAvatarUrl() ?: ($current->getAvatarUrl() ? 'data:image/png;base64,' . $current->getAvatarUrl() : '/images/Icon.svg');
            $user = [
                'avatar' => $avatar,
                'pseudo' => $current->getusername() ?: $current->getUserIdentifier(),
                'email' => $current->getEmail(),
                'joined' => '2024-08-12',
                'shortcuts' => [
                    ['label' => 'Favoris', 'href' => '#favorites'],
                    ['label' => 'Mes résumés', 'href' => '#summaries'],
                    ['label' => 'Paramètres', 'href' => '#settings'],
                ],
            ];
        } else {
            $session = $request->getSession();
            $avatar = $session->get('user_avatar', '/images/Icon.svg');

            $user = [
                'avatar' => $avatar,
                'pseudo' => 'Utilisateur17',
                'email' => 'user17@example.com',
                'joined' => '2024-08-12',
                'shortcuts' => [
                    ['label' => 'Favoris', 'href' => '#favorites'],
                    ['label' => 'Mes résumés', 'href' => '#summaries'],
                    ['label' => 'Paramètres', 'href' => '#settings'],
                ],
            ];
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/upload', name: 'app_profile_upload', methods: ['POST'])]
    public function upload(\Symfony\Component\HttpFoundation\Request $request, EntityManagerInterface $em): Response
    {
        $uploadedFile = $request->files->get('avatar');
        if (!($uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_profile');
        }

        // Server-side validation
        $allowedMime = ['image/png', 'image/jpeg'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($uploadedFile->getClientMimeType(), $allowedMime, true)) {
            $this->addFlash('error', 'Format invalide — seuls PNG et JPEG sont acceptés.');
            return $this->redirectToRoute('app_profile');
        }

        if ($uploadedFile->getSize() > $maxSize) {
            $this->addFlash('error', 'Fichier trop volumineux — taille maximale 2MB.');
            return $this->redirectToRoute('app_profile');
        }

        $current = $this->getUser();

        // If user is logged in, store image in DB (base64) and set avatarUrl to data URI
        if ($current instanceof User) {
            // Store a unique file per user, use user id in filename so each profile has its own image
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0755, true);
            }

            $extension = $uploadedFile->guessExtension() ?: 'png';
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $userId = $current->getId() ?? uniqid('user_', true);
            $newFilename = 'avatar_user_' . $userId . '.' . $extension;

            try {
                $uploadedFile->move($uploadsDir, $newFilename);

                $current->setAvatarUrl('/uploads/avatars/' . $newFilename);

                $em->persist($current);
                $em->flush();

                $this->addFlash('success', 'Avatar enregistré (fichier unique par utilisateur).');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Échec de l\'enregistrement de l\'avatar.');
            }
        } else {
            // fallback: store in session (previous behaviour)
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0755, true);
            }

            $extension = $uploadedFile->guessExtension() ?: 'png';
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            $newFilename = uniqid('avatar_', true) . '.' . $extension;

            try {
                $uploadedFile->move($uploadsDir, $newFilename);
                $request->getSession()->set('user_avatar', '/uploads/avatars/' . $newFilename);
                $this->addFlash('success', 'Avatar mis à jour en session.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Échec de l\'upload.');
            }
        }

        return $this->redirectToRoute('app_profile');
    }
}
