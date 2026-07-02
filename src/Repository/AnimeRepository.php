<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 */
class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    public function searchCatalogue(array $filters): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c')
            ->distinct();

        // 1. Ajout des parenthèses indispensables autour des OR
        if (!empty($filters['q'])) {
            $qb->andWhere('(LOWER(a.title) LIKE :q OR LOWER(a.synopsis) LIKE :q OR LOWER(a.author) LIKE :q)')
                ->setParameter('q', '%' . mb_strtolower($filters['q']) . '%');
        }

        if (!empty($filters['genre'])) {
            $qb->andWhere('c.slug = :genre')
                ->setParameter('genre', $filters['genre']);
        }

        // 2. Gestion adaptative si animeDate est un champ Date/DateTime en BDD
        if (!empty($filters['annee'])) {
            $qb->andWhere('a.animeDate = :annee')
                ->setParameter('annee', (int) $filters['annee']);
        }

        return $qb
            ->orderBy('a.title', "ASC")
            ->getQuery()
            ->getResult();
    }
}