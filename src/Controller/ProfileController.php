<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Summary;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $current = $this->getUser();
        $favorites = [];
        $summaries = [];
        $favoriteCount = 0;
        $summaryCount = 0;

        if ($current instanceof User) {
            $favoritesEntities = $em->getRepository(Favorite::class)->findBy(['user' => $current], ['createdAt' => 'DESC']);
            $summaryEntities = $em->getRepository(Summary::class)->findBy(['user' => $current], ['id' => 'DESC']);
            $favoriteCount = count($favoritesEntities);
            $summaryCount = count($summaryEntities);
            $favorites = array_map(fn (Favorite $favorite) => $this->buildFavoriteCard($favorite), $favoritesEntities);
            $summaries = array_map(fn (Summary $summary) => $this->buildSummaryCard($summary), $summaryEntities);

            $avatar = $current->getAvatarUrl() ?: '/images/Icon.svg';
            $user = [
                'avatar' => $avatar,
                'username' => $current->getusername() ?: $current->getUserIdentifier(),
                'pseudo' => $current->getusername() ?: $current->getUserIdentifier(),
                'email' => $current->getEmail(),
                'joined' => '2024-08-12',
                'shortcuts' => [
                    ['label' => 'Favoris', 'href' => '#favorites'],
                    ['label' => 'Mes résumés', 'href' => '#summaries'],
                    ['label' => 'Paramètres', 'href' => '#settings'],
                ],
                'favorites' => $favorites,
                'summaries' => $summaries,
                'favoritesCount' => $favoriteCount,
                'synopsisCount' => $summaryCount,
            ];
        } else {
            $session = $request->getSession();
            $avatar = $session->get('user_avatar', '/images/Icon.svg');
            $fallbackUser = $em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

            $favorites = [];
            if ($fallbackUser) {
                $favoritesEntities = $em->getRepository(Favorite::class)->findBy(['user' => $fallbackUser], ['createdAt' => 'DESC']);
                $favorites = array_map(fn (Favorite $favorite) => $this->buildFavoriteCard($favorite), $favoritesEntities);
                $summariesEntities = $em->getRepository(Summary::class)->findBy(['user' => $fallbackUser], ['id' => 'DESC']);
                $summaries = array_map(fn (Summary $summary) => $this->buildSummaryCard($summary), $summariesEntities);
                $favoriteCount = count($favoritesEntities);
                $summaryCount = count($summariesEntities);
            }

            $user = [
                'avatar' => $avatar,
                'username' => 'Utilisateur17',
                'pseudo' => 'Utilisateur17',
                'email' => 'user17@example.com',
                'joined' => '2024-08-12',
                'shortcuts' => [
                    ['label' => 'Favoris', 'href' => '#favorites'],
                    ['label' => 'Mes résumés', 'href' => '#summaries'],
                    ['label' => 'Paramètres', 'href' => '#settings'],
                ],
                'favorites' => $favorites,
                'summaries' => $summaries,
                'favoritesCount' => $favoriteCount,
                'synopsisCount' => $summaryCount,
            ];
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    private function buildFavoriteCard(Favorite $favorite): array
    {
        $title = $favorite->getAnime()?->getTitle()
            ?? $favorite->getSeason()?->getTitle()
            ?? $favorite->getManga()?->getTitle()
            ?? 'Œuvre';
        $type = $favorite->getAnime() ? 'Anime' : ($favorite->getSeason() ? 'Saison' : ($favorite->getManga() ? 'Manga' : 'Autre'));
        $image = $favorite->getAnime()?->getCoverAnimeUrl()
            ?? $favorite->getSeason()?->getCoverSeasonUrl()
            ?? $favorite->getManga()?->getCoverMangaUrl()
            ?? '/images/coverCardSeason.png';

        return [
            'type' => $type,
            'title' => $title,
            'range' => $favorite->getSeason()?->getTitle() ?? 'Favori enregistré',
            'modifiedDate' => $favorite->getCreatedAt()?->format('d.m.Y') ?? '—',
            'votes' => '25k Votes',
            'image' => $image,
            'isFavorite' => true,
        ];
    }

    private function buildSummaryCard(Summary $summary): array
    {
        $title = $summary->getTitle()
            ?? $summary->getAnime()?->getTitle()
            ?? $summary->getSeason()?->getTitle()
            ?? $summary->getManga()?->getTitle()
            ?? 'Résumé';

        return [
            'type' => $summary->getManga() ? 'Manga' : ($summary->getAnime() ? 'Anime' : 'Autre'),
            'title' => $title,
            'range' => 'Résumé disponible',
            'modifiedDate' => '10.05.2025',
            'votes' => '15k Votes',
            'image' => $summary->getAnime()?->getCoverAnimeUrl()
                ?? $summary->getManga()?->getCoverMangaUrl()
                ?? $summary->getSeason()?->getCoverSeasonUrl()
                ?? '/images/coverCardSeason.png',
            'isFavorite' => false,
        ];
    }

    #[Route('/profile/upload', name: 'app_profile_upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $uploadedFile = $request->files->get('avatar');
        if (!($uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_profile');
        }

        $allowedMime = ['image/png', 'image/jpeg'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($uploadedFile->getClientMimeType(), $allowedMime, true)) {
            $this->addFlash('error', 'Format invalide — seuls PNG et JPEG sont acceptés.');
            return $this->redirectToRoute('app_profile');
        }

        if ($uploadedFile->getSize() > $maxSize) {
            $this->addFlash('error', 'Fichier trop volumineux — taille maximale 2MB.');
            return $this->redirectToRoute('app_profile');
        }

        $current = $this->getUser();

        if ($current instanceof User) {
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
