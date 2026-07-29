<?php

namespace App\Repository;

use App\Entity\Manga;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Manga>
 */
class MangaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manga::class);
    }

    public function searchCatalogue(array $filters): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.categorie', 'c')
            ->addSelect('c')
            ->andWhere('m.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->distinct();

        // 1. Ajout des parenthèses indispensables autour des OR
        if (!empty($filters['q'])) {
            $qb->andWhere('(LOWER(m.title) LIKE :q OR LOWER(m.synopsis) LIKE :q OR LOWER(m.author) LIKE :q)')
                ->setParameter('q', '%' . mb_strtolower($filters['q']) . '%');
        }

        if (!empty($filters['genre'])) {
            $qb->andWhere('c.slug = :genre')
                ->setParameter('genre', $filters['genre']);
        }

        // 2. Gestion adaptative si mangaDate est un champ Date/DateTime en BDD
        if (!empty($filters['annee'])) {
            $qb->andWhere('m.mangaDate = :annee')
                ->setParameter('annee', (int) $filters['annee']);
        }

        return $qb
            ->orderBy('m.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Manga[]
     */
    public function findPublic(): array
    {
        return $this->findBy(['isPublic' => true], ['title' => 'ASC']);
    }

    public function findOnePublic(): ?Manga
    {
        return $this->findOneBy(['isPublic' => true], ['id' => 'ASC']);
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Manga
    {
        return $this->findOneBy([
            'coverMangaUrl' => $coverUrl,
            'owner' => $owner,
        ]);
    }
}
