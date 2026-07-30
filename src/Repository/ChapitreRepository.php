<?php

namespace App\Repository;

use App\Entity\Chapitre;
use App\Entity\Manga;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chapitre>
 */
class ChapitreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chapitre::class);
    }

    public function findOneOwned(int $id, User $owner): ?Chapitre
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Chapitre[]
     */
    public function findOwnedByManga(Manga $manga, User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('c.manga = :manga')
            ->setParameter('manga', $manga)
            ->orderBy('c.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Chapitre
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('c.coverChapitreUrl = :coverUrl')
            ->setParameter('coverUrl', $coverUrl)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Chapitre[]
     */
    public function findOwnedForSceneSelection(User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)
            ->orderBy('m.title', 'ASC')
            ->addOrderBy('c.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function numberExistsForManga(Manga $manga, int $number, ?int $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.manga = :manga')
            ->andWhere('c.number = :number')
            ->setParameter('manga', $manga)
            ->setParameter('number', $number);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('c.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    private function createOwnedQueryBuilder(User $owner): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.manga', 'm')
            ->leftJoin('c.categorie', 'category')
            ->addSelect('m', 'category')
            ->andWhere('c.user = :owner')
            ->andWhere('m.owner = :owner')
            ->andWhere('m.isPublic = :isPublic')
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false);
    }
}
