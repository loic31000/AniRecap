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
    public function findAllWithRelationsByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->addSelect('e', 's', 'a', 't', 'tm', 'c', 'cm', 'cat')
            ->leftJoin('d.episode', 'e')
            ->leftJoin('e.season', 's')
            ->leftJoin('s.anime', 'a')
            ->leftJoin('d.tome', 't')
            ->leftJoin('t.manga', 'tm')
            ->leftJoin('d.chapitre', 'c')
            ->leftJoin('c.manga', 'cm')
            ->leftJoin('d.categorie', 'cat')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
