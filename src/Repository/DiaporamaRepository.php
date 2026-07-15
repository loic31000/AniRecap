<?php

namespace App\Repository;

use App\Entity\Diaporama;
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


    public function findAllWithRelations(): array
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
        ->orderBy('d.id', 'ASC')
        ->getQuery()
        ->getResult();
}

}