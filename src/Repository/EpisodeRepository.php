<?php

namespace App\Repository;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Episode>
 */
class EpisodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Episode::class);
    }

    public function findOneOwned(int $id, User $owner): ?Episode
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Episode[]
     */
    public function findOwnedBySeason(Season $season, User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('e.season = :season')
            ->setParameter('season', $season)
            ->orderBy('e.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Episode
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('e.coverEpisodeUrl = :coverUrl')
            ->setParameter('coverUrl', $coverUrl)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Episode[]
     */
    public function findOwnedForSceneSelection(User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)
            ->orderBy('a.title', 'ASC')
            ->addOrderBy('s.number', 'ASC')
            ->addOrderBy('e.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function numberExistsForSeason(Season $season, int $number, ?int $excludedEpisodeId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.season = :season')
            ->andWhere('e.number = :number')
            ->setParameter('season', $season)
            ->setParameter('number', $number);

        if ($excludedEpisodeId !== null) {
            $queryBuilder
                ->andWhere('e.id != :excludedEpisodeId')
                ->setParameter('excludedEpisodeId', $excludedEpisodeId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    private function createOwnedQueryBuilder(User $owner): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.season', 's')
            ->innerJoin('s.anime', 'a')
            ->leftJoin('e.categorie', 'c')
            ->addSelect('s', 'a', 'c')
            ->andWhere('e.user = :owner')
            ->andWhere('a.owner = :owner')
            ->setParameter('owner', $owner);
    }
}
