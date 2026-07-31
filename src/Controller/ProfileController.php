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
        $summaryStates = $summaryRepository->findRootManagementStates(
            $current,
            array_column(array_filter($favoriteCards, static fn (array $card): bool => $card['type'] === 'anime'), 'id'),
            array_column(array_filter($favoriteCards, static fn (array $card): bool => $card['type'] === 'manga'), 'id'),
        );
        foreach ($favoriteCards as &$card) {
            $card['summaryManagement'] = $summaryStates[$card['type']][$card['id']] ?? null;
        }
        unset($card);
        $summaryCount = $summaryRepository->count(['user' => $current]);
        $summaryCards = array_map(
            fn (Summary $summary): array => $this->buildSummaryCard($summary, $current),
            $summaryRepository->findPreviewByUser($current, 4),
        );
        $user = [
            'avatar' => $current->getAvatarUrl() ?: '/images/Icon.svg',
            'username' => $current->getusername() ?: $current->getUserIdentifier(),
            'email' => $current->getEmail(),
            'favorites' => array_slice(array_values($favoriteCards), 0, 2),
            'favoritesCount' => count($favoriteCards),
            'synopsisCount' => $summaryCount,
        ];

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'summary_cards' => $summaryCards,
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
            'title' => $work->getTitle(),
            'subtitle' => $work->getStatus() ?: 'Favori enregistré',
            'date' => $favorite->getCreatedAt()?->format('d.m.Y') ?? '',
            'dateLabel' => 'Ajouté le',
            'cover' => $anime?->getCoverAnimeUrl() ?? $manga?->getCoverMangaUrl() ?? '/images/coverCardSeason.png',
            'votesCount' => $work->getVotes()->count(),
            'isPrivate' => !$work->isPublic(),
            'isFavorite' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function buildSummaryCard(Summary $summary, User $owner): array
    {
        $parent = $summary->getAnime() ?? $summary->getManga() ?? $summary->getSeason()
            ?? $summary->getEpisode() ?? $summary->getTome() ?? $summary->getChapitre();
        $directUserMatches = !is_object($parent)
            || !method_exists($parent, 'getUser')
            || $parent->getUser()?->getId() === $owner->getId();
        $parentIsOwned = $parent?->getOwner()?->getId() === $owner->getId() && $directUserMatches;

        [$editRoute, $editId] = $parentIsOwned ? match (true) {
            $summary->getAnime() !== null => ['app_forms_anime_edit', $summary->getAnime()->getId()],
            $summary->getManga() !== null => ['app_forms_manga_edit', $summary->getManga()->getId()],
            $summary->getSeason() !== null => ['app_forms_season_edit', $summary->getSeason()->getId()],
            $summary->getEpisode() !== null => ['app_forms_episode_edit', $summary->getEpisode()->getId()],
            $summary->getTome() !== null => ['app_forms_tome_edit', $summary->getTome()->getId()],
            $summary->getChapitre() !== null => ['app_forms_chapitre_edit', $summary->getChapitre()->getId()],
            default => ['app_summary_edit', $summary->getId()],
        } : ['app_summary_edit', $summary->getId()];

        return [
            'id' => $summary->getId(),
            'type' => 'Résumé',
            'title' => $summary->getTitle(),
            'description' => $summary->getContent(),
            'meta' => $this->summaryParentLabel($summary),
            'image' => $summary->getAnime()?->getCoverAnimeUrl()
                ?? $summary->getManga()?->getCoverMangaUrl()
                ?? $summary->getSeason()?->getCoverSeasonUrl()
                ?? $summary->getEpisode()?->getCoverEpisodeUrl()
                ?? $summary->getTome()?->getCoverTomeUrl()
                ?? $summary->getChapitre()?->getCoverChapitreUrl(),
            'openRoute' => 'app_summary_show',
            'editRoute' => $editRoute,
            'editId' => $editId,
            'deleteRoute' => 'app_summary_delete_confirm',
        ];
    }

    private function summaryParentLabel(Summary $summary): string
    {
        return $summary->getAnime()?->getTitle()
            ?? $summary->getManga()?->getTitle()
            ?? $summary->getSeason()?->getTitle()
            ?? $summary->getEpisode()?->getTitle()
            ?? $summary->getTome()?->getTitle()
            ?? $summary->getChapitre()?->getTitle()
            ?? 'Aucun parent renseigné';
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
