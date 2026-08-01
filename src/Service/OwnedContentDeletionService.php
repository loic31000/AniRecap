<?php

namespace App\Service;

use App\Entity\Chapitre;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Tome;
use Doctrine\ORM\EntityManagerInterface;

final class OwnedContentDeletionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SynopsisImageUploader $imageUploader,
    ) {
    }

    public function deleteSeason(Season $season): void
    {
        $filenames = $this->filenameFromUrl($season->getCoverSeasonUrl());

        $this->entityManager->wrapInTransaction(function () use ($season, &$filenames): void {
            foreach ($season->getEpisodes()->toArray() as $episode) {
                $filenames = [...$filenames, ...$this->removeEpisode($episode)];
            }

            $this->removeRelations($season->getSummaries()->toArray());
            $this->removeRelations($season->getFavorites()->toArray());
            $this->entityManager->remove($season);
        });

        $this->removeFiles($filenames);
    }

    public function deleteEpisode(Episode $episode): void
    {
        $filenames = [];
        $this->entityManager->wrapInTransaction(function () use ($episode, &$filenames): void {
            $filenames = $this->removeEpisode($episode);
        });
        $this->removeFiles($filenames);
    }

    public function deleteTome(Tome $tome): void
    {
        $filenames = [];
        $this->entityManager->wrapInTransaction(function () use ($tome, &$filenames): void {
            $filenames = $this->removeMangaChild($tome);
        });
        $this->removeFiles($filenames);
    }

    public function deleteChapitre(Chapitre $chapitre): void
    {
        $filenames = [];
        $this->entityManager->wrapInTransaction(function () use ($chapitre, &$filenames): void {
            $filenames = $this->removeMangaChild($chapitre);
        });
        $this->removeFiles($filenames);
    }

    /** @return string[] */
    private function removeEpisode(Episode $episode): array
    {
        $filenames = $this->filenameFromUrl($episode->getCoverEpisodeUrl());
        foreach ($episode->getSlides()->toArray() as $slide) {
            if ($slide->getImageFilename() !== null) {
                $filenames[] = $slide->getImageFilename();
            }
            $this->entityManager->remove($slide);
        }

        $this->removeRelations($episode->getSummaries()->toArray());
        $this->removeRelations($episode->getFavorites()->toArray());
        $this->removeRelations($episode->getSpoilerPreferences()->toArray());
        $this->entityManager->remove($episode);

        return $filenames;
    }

    /** @return string[] */
    private function removeMangaChild(Tome|Chapitre $child): array
    {
        $coverUrl = $child instanceof Tome ? $child->getCoverTomeUrl() : $child->getCoverChapitreUrl();
        $filenames = $this->filenameFromUrl($coverUrl);

        foreach ($child->getSlides()->toArray() as $slide) {
            if ($slide->getImageFilename() !== null) {
                $filenames[] = $slide->getImageFilename();
            }
            $this->entityManager->remove($slide);
        }

        $this->removeRelations($child->getSummaries()->toArray());
        $this->removeRelations($child->getFavorites()->toArray());
        $this->removeRelations($child->getSpoilerPreferences()->toArray());
        $this->entityManager->remove($child);

        return $filenames;
    }

    /** @param object[] $relations */
    private function removeRelations(array $relations): void
    {
        foreach ($relations as $relation) {
            $this->entityManager->remove($relation);
        }
    }

    /** @return string[] */
    private function filenameFromUrl(?string $url): array
    {
        if ($url === null) {
            return [];
        }

        $filename = basename((string) parse_url($url, PHP_URL_PATH));

        return preg_match('/\A[a-f0-9]{32}\.(?:png|jpg)\z/', $filename) === 1 ? [$filename] : [];
    }

    /** @param string[] $filenames */
    private function removeFiles(array $filenames): void
    {
        foreach (array_unique($filenames) as $filename) {
            $this->imageUploader->remove($filename);
        }
    }
}
