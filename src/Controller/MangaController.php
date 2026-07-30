<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MangaRepository;
use App\Repository\TomeRepository;
use App\Repository\ChapitreRepository;
use App\Repository\DiaporamaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MangaController extends AbstractController
{
    #[Route('/manga/{id}', name: 'app_manga_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        MangaRepository $mangaRepository,
        TomeRepository $tomeRepository,
        ChapitreRepository $chapitreRepository,
        DiaporamaRepository $diaporamaRepository,
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $manga = $mangaRepository->findOneVisibleTo($id, $user);
        if ($manga === null) {
            throw $this->createNotFoundException();
        }

        $isOwnerPrivate = !$manga->isPublic() && $manga->getOwner()?->getId() === $user->getId();
        $tomes = $isOwnerPrivate ? $tomeRepository->findOwnedByManga($manga, $user) : [];
        $chapitres = $isOwnerPrivate ? $chapitreRepository->findOwnedByManga($manga, $user) : [];
        $tomeIds = array_map(static fn ($tome): int => (int) $tome->getId(), $tomes);
        $chapitreIds = array_map(static fn ($chapitre): int => (int) $chapitre->getId(), $chapitres);

        return $this->render('manga/index.html.twig', [
            'manga' => $manga,
            'is_owner_private' => $isOwnerPrivate,
            'tomes' => $tomes,
            'chapitres' => $chapitres,
            'tome_diaporamas' => $diaporamaRepository->findOwnedLinksForTomes($tomeIds, $user),
            'chapitre_diaporamas' => $diaporamaRepository->findOwnedLinksForChapitres($chapitreIds, $user),
            'characters' => $manga->getCharacters()->toArray(),
        ]);
    }
}
