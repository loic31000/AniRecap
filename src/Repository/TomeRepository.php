<?php

namespace App\Repository;

use App\Entity\Manga;
use App\Entity\Tome;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tome>
 */
class TomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tome::class);
    }

    public function findOneOwned(int $id, User $owner): ?Tome
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Tome[]
     */
    public function findOwnedByManga(Manga $manga, User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('t.manga = :manga')
            ->setParameter('manga', $manga)
            ->orderBy('t.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Tome
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('t.coverTomeUrl = :coverUrl')
            ->setParameter('coverUrl', $coverUrl)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Tome[]
     */
    public function findOwnedForSceneSelection(User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)
            ->orderBy('m.title', 'ASC')
            ->addOrderBy('t.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function numberExistsForManga(Manga $manga, int $number, ?int $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.manga = :manga')
            ->andWhere('t.number = :number')
            ->setParameter('manga', $manga)
            ->setParameter('number', $number);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('t.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    private function createOwnedQueryBuilder(User $owner): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.manga', 'm')
            ->leftJoin('t.categorie', 'c')
            ->addSelect('m', 'c')
            ->andWhere('t.user = :owner')
            ->andWhere('m.owner = :owner')
            ->andWhere('m.isPublic = :isPublic')
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false);
    }
}
