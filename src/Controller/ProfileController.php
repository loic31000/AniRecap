<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Summary;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function upload(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): Response
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('avatar_upload', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $uploadedFile = $request->files->get('avatar');
        if (!$uploadedFile instanceof UploadedFile) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');

            return $this->redirectToRoute('app_profile');
        }

        if (!$uploadedFile->isValid()) {
            $this->addFlash('error', 'Le téléversement du fichier a échoué.');

            return $this->redirectToRoute('app_profile');
        }

        $violations = $validator->validate($uploadedFile, new Assert\Image(
            maxSize: '2M',
            mimeTypes: ['image/png', 'image/jpeg'],
            extensions: ['png', 'jpg', 'jpeg'],
            maxWidth: 4096,
            maxHeight: 4096,
            maxPixels: 16_777_216,
            detectCorrupted: true,
        ));

        if (count($violations) > 0) {
            $this->addFlash('error', (string) $violations[0]->getMessage());

            return $this->redirectToRoute('app_profile');
        }

        $extension = match ($uploadedFile->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => null,
        };

        if ($extension === null) {
            $this->addFlash('error', 'Format invalide — seuls PNG et JPEG sont acceptés.');

            return $this->redirectToRoute('app_profile');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        $oldAvatarUrl = $current->getAvatarUrl();
        $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $newAvatarUrl = '/uploads/avatars/' . $newFilename;
        $newFilePath = $uploadsDir . DIRECTORY_SEPARATOR . $newFilename;

        try {
            if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
                throw new \RuntimeException('Impossible de créer le répertoire des avatars.');
            }

            $uploadedFile->move($uploadsDir, $newFilename);

            $current->setAvatarUrl($newAvatarUrl);
            $em->flush();
        } catch (\Throwable) {
            $current->setAvatarUrl($oldAvatarUrl);

            if (is_file($newFilePath)) {
                unlink($newFilePath);
            }

            $this->addFlash('error', 'Échec de l\'enregistrement de l\'avatar.');

            return $this->redirectToRoute('app_profile');
        }

        $this->removeManagedAvatar($oldAvatarUrl, $uploadsDir, $newFilePath);
        $this->addFlash('success', 'Avatar enregistré.');

        return $this->redirectToRoute('app_profile');
    }

    private function removeManagedAvatar(?string $avatarUrl, string $uploadsDir, string $currentAvatarPath): void
    {
        $urlPrefix = '/uploads/avatars/';
        if ($avatarUrl === null || !str_starts_with($avatarUrl, $urlPrefix)) {
            return;
        }

        $filename = substr($avatarUrl, strlen($urlPrefix));
        if ($filename === '' || basename($filename) !== $filename) {
            return;
        }

        $managedDirectory = realpath($uploadsDir);
        $avatarPath = realpath($uploadsDir . DIRECTORY_SEPARATOR . $filename);
        if ($managedDirectory === false || $avatarPath === false || $avatarPath === $currentAvatarPath) {
            return;
        }

        if (!str_starts_with($avatarPath, $managedDirectory . DIRECTORY_SEPARATOR) || !is_file($avatarPath)) {
            return;
        }

        unlink($avatarPath);
    }
}
