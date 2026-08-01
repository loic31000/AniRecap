<?php

namespace App\Repository;

use App\Entity\Diaporama;
use App\Entity\Slide;
use App\Entity\User;
use App\Enum\SpoilerLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slide>
 */
final class SlideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slide::class);
    }

    public function nextPosition(Diaporama $diaporama): int
    {
        $lastPosition = $this->createQueryBuilder('s')
            ->select('MAX(s.position)')
            ->andWhere('s.diaporama = :diaporama')
            ->setParameter('diaporama', $diaporama)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $lastPosition) + 1;
    }

    public function findOneOwnedInDiaporama(int $slideId, int $diaporamaId, User $owner): ?Slide
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.diaporama', 'd')
            ->addSelect('d')
            ->andWhere('s.id = :slideId')
            ->andWhere('d.id = :diaporamaId')
            ->andWhere('d.user = :owner')
            ->setParameter('slideId', $slideId)
            ->setParameter('diaporamaId', $diaporamaId)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countFilenameReferences(string $filename): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.imageFilename = :filename')
            ->setParameter('filename', $filename)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<int, SpoilerLevel> */
    public function findHighestLevelsForEpisodes(array $episodeIds): array
    {
        return $this->findHighestLevels('episode', $episodeIds);
    }

    /** @return array<int, SpoilerLevel> */
    public function findHighestLevelsForTomes(array $tomeIds): array
    {
        return $this->findHighestLevels('tome', $tomeIds);
    }

    /** @return array<int, SpoilerLevel> */
    public function findHighestLevelsForChapitres(array $chapitreIds): array
    {
        return $this->findHighestLevels('chapitre', $chapitreIds);
    }

    /** @return array<int, SpoilerLevel> */
    private function findHighestLevels(string $association, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('slide')
            ->select(sprintf('IDENTITY(slide.%s) AS targetId', $association), 'slide.spoilerLevel AS level')
            ->andWhere(sprintf('slide.%s IN (:ids)', $association))
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $levels = [];
        foreach ($rows as $row) {
            $id = (int) $row['targetId'];
            $level = $row['level'] instanceof SpoilerLevel
                ? $row['level']
                : SpoilerLevel::from((string) $row['level']);
            if (!isset($levels[$id]) || $this->rank($level) > $this->rank($levels[$id])) {
                $levels[$id] = $level;
            }
        }

        return $levels;
    }

    private function rank(SpoilerLevel $level): int
    {
        return match ($level) {
            SpoilerLevel::Aucun => 0,
            SpoilerLevel::Mineur => 1,
            SpoilerLevel::Majeur => 2,
        };
    }
}
