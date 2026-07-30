<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * @return Favorite[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.anime', 'a')
            ->leftJoin('f.manga', 'm')
            ->leftJoin('f.season', 's')
            ->leftJoin('s.anime', 'sa')
            ->leftJoin('f.episode', 'e')
            ->leftJoin('e.season', 'es')
            ->leftJoin('es.anime', 'ea')
            ->addSelect('a', 'm', 's')
            ->andWhere('f.user = :user')
            ->andWhere('(a.id IS NULL OR a.isPublic = :isPublic OR a.owner = :user)')
            ->andWhere('(m.id IS NULL OR m.isPublic = :isPublic OR m.owner = :user)')
            ->andWhere('(s.id IS NULL OR sa.isPublic = :isPublic OR sa.owner = :user)')
            ->andWhere('(e.id IS NULL OR ea.isPublic = :isPublic OR ea.owner = :user)')
            ->setParameter('user', $user)
            ->setParameter('isPublic', true)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
