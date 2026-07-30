<?php

namespace App\Repository;

use App\Entity\Summary;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Summary>
 */
class SummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Summary::class);
    }

    /**
     * @return Summary[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.anime', 'a')
            ->leftJoin('s.manga', 'm')
            ->leftJoin('s.season', 'season')
            ->leftJoin('season.anime', 'sa')
            ->leftJoin('s.episode', 'e')
            ->leftJoin('e.season', 'es')
            ->leftJoin('es.anime', 'ea')
            ->addSelect('a', 'm', 'season')
            ->andWhere('s.user = :user')
            ->andWhere('(a.id IS NULL OR a.isPublic = :isPublic OR a.owner = :user)')
            ->andWhere('(m.id IS NULL OR m.isPublic = :isPublic OR m.owner = :user)')
            ->andWhere('(season.id IS NULL OR sa.isPublic = :isPublic OR sa.owner = :user)')
            ->andWhere('(e.id IS NULL OR ea.isPublic = :isPublic OR ea.owner = :user)')
            ->setParameter('user', $user)
            ->setParameter('isPublic', true)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
