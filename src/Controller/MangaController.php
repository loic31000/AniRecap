<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MangaRepository;
use App\Repository\TomeRepository;
use App\Repository\ChapitreRepository;
use App\Repository\DiaporamaRepository;
use App\Repository\SlideRepository;
use App\Repository\FavoriteRepository;
use App\Repository\CharacterRepository;
use App\Enum\SpoilerLevel;
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
        SlideRepository $slideRepository,
        FavoriteRepository $favoriteRepository,
        CharacterRepository $characterRepository,
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
        $tomeSlideLevels = $slideRepository->findHighestLevelsForTomes($tomeIds);
        $chapitreSlideLevels = $slideRepository->findHighestLevelsForChapitres($chapitreIds);
        $tomeLevels = [];
        foreach ($tomes as $tome) {
            $tomeLevels[$tome->getId()] = $this->highestLevel($tome->getSpoilerLevel(), $tomeSlideLevels[$tome->getId()] ?? SpoilerLevel::Aucun);
        }
        $chapitreLevels = [];
        foreach ($chapitres as $chapitre) {
            $chapitreLevels[$chapitre->getId()] = $this->highestLevel($chapitre->getSpoilerLevel(), $chapitreSlideLevels[$chapitre->getId()] ?? SpoilerLevel::Aucun);
        }
        $favoriteState = $favoriteRepository->findRootFavoriteStates($user, [], [$manga->getId()]);

        return $this->render('manga/index.html.twig', [
            'manga' => $manga,
            'is_owner_private' => $isOwnerPrivate,
            'tomes' => $tomes,
            'chapitres' => $chapitres,
            'tome_diaporamas' => $diaporamaRepository->findOwnedLinksForTomes($tomeIds, $user),
            'chapitre_diaporamas' => $diaporamaRepository->findOwnedLinksForChapitres($chapitreIds, $user),
            'characters' => $characterRepository->findOwnedByManga($manga, $user),
            'tome_spoiler_levels' => $tomeLevels,
            'chapitre_spoiler_levels' => $chapitreLevels,
            'favorite_oeuvre' => ['id' => $manga->getId(), 'type' => 'manga', 'title' => $manga->getTitle(), 'isFavorite' => $favoriteState['manga'][$manga->getId()] ?? false],
        ]);
    }

    private function highestLevel(SpoilerLevel $first, SpoilerLevel $second): SpoilerLevel
    {
        $rank = static fn (SpoilerLevel $level): int => match ($level) {
            SpoilerLevel::Aucun => 0,
            SpoilerLevel::Mineur => 1,
            SpoilerLevel::Majeur => 2,
        };

        return $rank($first) >= $rank($second) ? $first : $second;
    }
}
