<?php

namespace App\Repository;

use App\Entity\Diaporama;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Diaporama>
 */
class DiaporamaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Diaporama::class);
    }

    /**
     * @return Diaporama[]
     */
    public function findOwnedByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.categorie', 'cat')
            ->addSelect('cat')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Diaporama[]
     */
    public function findOwnedByUserWithSlides(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.slides', 's')->addSelect('s')
            ->leftJoin('d.categorie', 'cat')->addSelect('cat')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.title', 'ASC')
            ->addOrderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countFilenameReferences(string $filename): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.coverImageFilename = :filename')
            ->setParameter('filename', $filename)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneOwned(int $id, User $user): ?Diaporama
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id = :id')
            ->andWhere('d.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneOwnedWithSlides(int $id, User $user): ?Diaporama
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.slides', 's')
            ->addSelect('s')
            ->leftJoin('s.episode', 'e')
            ->leftJoin('e.season', 'season')
            ->leftJoin('season.anime', 'anime')
            ->leftJoin('s.tome', 't')
            ->leftJoin('t.manga', 'tomeManga')
            ->leftJoin('s.chapitre', 'c')
            ->leftJoin('c.manga', 'chapitreManga')
            ->addSelect('e', 'season', 'anime', 't', 'tomeManga', 'c', 'chapitreManga')
            ->andWhere('d.id = :id')
            ->andWhere('d.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int[] $episodeIds
     *
     * @return array<int, Diaporama[]>
     */
    public function findOwnedLinksForEpisodes(array $episodeIds, User $owner): array
    {
        if ($episodeIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('d')
            ->select('DISTINCT d', 'e.id AS targetId')
            ->innerJoin('d.slides', 's')
            ->innerJoin('s.episode', 'e')
            ->innerJoin('e.season', 'season')
            ->innerJoin('season.anime', 'anime')
            ->andWhere('e.id IN (:targetIds)')
            ->andWhere('d.user = :owner')
            ->andWhere('e.user = :owner')
            ->andWhere('anime.owner = :owner')
            ->andWhere('anime.isPublic = :isPublic')
            ->setParameter('targetIds', $episodeIds)
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false)
            ->orderBy('d.title', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->groupLinkedDiaporamas($rows);
    }

    /**
     * @param int[] $tomeIds
     *
     * @return array<int, Diaporama[]>
     */
    public function findOwnedLinksForTomes(array $tomeIds, User $owner): array
    {
        if ($tomeIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('d')
            ->select('DISTINCT d', 't.id AS targetId')
            ->innerJoin('d.slides', 's')
            ->innerJoin('s.tome', 't')
            ->innerJoin('t.manga', 'manga')
            ->andWhere('t.id IN (:targetIds)')
            ->andWhere('d.user = :owner')
            ->andWhere('t.user = :owner')
            ->andWhere('manga.owner = :owner')
            ->andWhere('manga.isPublic = :isPublic')
            ->setParameter('targetIds', $tomeIds)
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false)
            ->orderBy('d.title', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->groupLinkedDiaporamas($rows);
    }

    /**
     * @param int[] $chapitreIds
     *
     * @return array<int, Diaporama[]>
     */
    public function findOwnedLinksForChapitres(array $chapitreIds, User $owner): array
    {
        if ($chapitreIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('d')
            ->select('DISTINCT d', 'c.id AS targetId')
            ->innerJoin('d.slides', 's')
            ->innerJoin('s.chapitre', 'c')
            ->innerJoin('c.manga', 'manga')
            ->andWhere('c.id IN (:targetIds)')
            ->andWhere('d.user = :owner')
            ->andWhere('c.user = :owner')
            ->andWhere('manga.owner = :owner')
            ->andWhere('manga.isPublic = :isPublic')
            ->setParameter('targetIds', $chapitreIds)
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false)
            ->orderBy('d.title', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->groupLinkedDiaporamas($rows);
    }

    /**
     * @param array<int, array{0: Diaporama, targetId: int|string}> $rows
     *
     * @return array<int, Diaporama[]>
     */
    private function groupLinkedDiaporamas(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $targetId = (int) $row['targetId'];
            $grouped[$targetId][] = $row[0];
        }

        return $grouped;
    }
}
