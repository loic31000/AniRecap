<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Summary;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\SummaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(
        FavoriteRepository $favoriteRepository,
        SummaryRepository $summaryRepository,
    ): Response
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $favoritesEntities = $favoriteRepository->findByUser($current);
        $favoriteCards = [];
        foreach ($favoritesEntities as $favorite) {
            $card = $this->buildFavoriteCard($favorite);
            if ($card !== null) {
                $favoriteCards[$card['type'] . ':' . $card['id']] = $card;
            }
        }
        $states = $favoriteRepository->findRootFavoriteStates(
            $current,
            array_column(array_filter($favoriteCards, static fn (array $card): bool => $card['type'] === 'anime'), 'id'),
            array_column(array_filter($favoriteCards, static fn (array $card): bool => $card['type'] === 'manga'), 'id'),
        );
        foreach ($favoriteCards as &$card) {
            $card['isFavorite'] = $states[$card['type']][$card['id']] ?? false;
        }
        unset($card);
        $summaryEntities = $summaryRepository->findByUser($current);
        $user = [
            'avatar' => $current->getAvatarUrl() ?: '/images/Icon.svg',
            'username' => $current->getusername() ?: $current->getUserIdentifier(),
            'email' => $current->getEmail(),
            'favorites' => array_values($favoriteCards),
            'summaries' => array_map(fn (Summary $summary) => $this->buildSummaryCard($summary), $summaryEntities),
            'favoritesCount' => count($favoriteCards),
            'synopsisCount' => count($summaryEntities),
        ];

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    private function buildFavoriteCard(Favorite $favorite): ?array
    {
        $anime = $favorite->getAnime() ?? $favorite->getSeason()?->getAnime() ?? $favorite->getEpisode()?->getSeason()?->getAnime();
        $manga = $favorite->getManga() ?? $favorite->getTome()?->getManga() ?? $favorite->getChapitre()?->getManga();
        if ($anime === null && $manga === null) {
            return null;
        }
        $type = $anime !== null ? 'anime' : 'manga';
        $work = $anime ?? $manga;

        return [
            'id' => $work->getId(),
            'type' => $type,
            'typeLabel' => ucfirst($type),
            'title' => $work->getTitle(),
            'range' => $work->getStatus() ?: 'Favori enregistré',
            'modifiedDate' => $favorite->getCreatedAt()?->format('d.m.Y') ?? '—',
            'image' => $anime?->getCoverAnimeUrl() ?? $manga?->getCoverMangaUrl() ?? '/images/coverCardSeason.png',
            'isFavorite' => false,
            'url' => $this->generateUrl($type === 'anime' ? 'app_anime_show' : 'app_manga_show', ['id' => $work->getId()]),
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
            'modifiedDate' => '—',
            'image' => $summary->getAnime()?->getCoverAnimeUrl()
                ?? $summary->getManga()?->getCoverMangaUrl()
                ?? $summary->getSeason()?->getCoverSeasonUrl()
                ?? '/images/coverCardSeason.png',
            'isFavorite' => false,
            'url' => $summary->getAnime() !== null
                ? $this->generateUrl('app_anime_show', ['id' => $summary->getAnime()->getId()])
                : ($summary->getManga() !== null
                    ? $this->generateUrl('app_manga_show', ['id' => $summary->getManga()->getId()])
                    : null),
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
