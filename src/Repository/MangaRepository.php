<?php

namespace App\Repository;

use App\Entity\Manga;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return Manga[]
     */
    public function searchVisibleTo(array $filters, User $viewer): array
    {
        $qb = $this->createVisibleQueryBuilder($viewer);

        if (!empty($filters['q'])) {
            $qb->andWhere("(LOWER(m.title) LIKE :q ESCAPE '!' OR LOWER(m.synopsis) LIKE :q ESCAPE '!' OR LOWER(m.author) LIKE :q ESCAPE '!')")
                ->setParameter('q', $filters['q_pattern']);
        }

        if (!empty($filters['genre'])) {
            $qb->andWhere('c.slug = :genre')
                ->setParameter('genre', $filters['genre']);
        }

        if (!empty($filters['date'])) {
            $qb->andWhere('m.releaseDate = :releaseDate')
                ->setParameter('releaseDate', new \DateTimeImmutable($filters['date']));
        } elseif (!empty($filters['annee'])) {
            $year = (int) $filters['annee'];
            $qb->andWhere('m.releaseDate >= :yearStart AND m.releaseDate < :yearEnd')
                ->setParameter('yearStart', new \DateTimeImmutable(sprintf('%d-01-01', $year)))
                ->setParameter('yearEnd', new \DateTimeImmutable(sprintf('%d-01-01', $year + 1)));
        }

        return $qb
            ->orderBy('m.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Manga[] */
    public function searchPublic(array $filters): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.categorie', 'c')->addSelect('c')
            ->andWhere('m.isPublic = true')->distinct();
        if (!empty($filters['q'])) {
            $qb->andWhere("(LOWER(m.title) LIKE :q ESCAPE '!' OR LOWER(m.synopsis) LIKE :q ESCAPE '!' OR LOWER(m.author) LIKE :q ESCAPE '!')")
                ->setParameter('q', $filters['q_pattern']);
        }
        if (!empty($filters['genre'])) { $qb->andWhere('c.slug = :genre')->setParameter('genre', $filters['genre']); }
        if (!empty($filters['date'])) { $qb->andWhere('m.releaseDate = :releaseDate')->setParameter('releaseDate', new \DateTimeImmutable($filters['date'])); }
        elseif (!empty($filters['annee'])) {
            $year = (int) $filters['annee'];
            $qb->andWhere('m.releaseDate >= :yearStart AND m.releaseDate < :yearEnd')
                ->setParameter('yearStart', new \DateTimeImmutable(sprintf('%d-01-01', $year)))
                ->setParameter('yearEnd', new \DateTimeImmutable(sprintf('%d-01-01', $year + 1)));
        }
        return $qb->orderBy('m.title', 'ASC')->getQuery()->getResult();
    }

    public function findOneVisibleTo(int $id, User $viewer): ?Manga
    {
        return $this->createVisibleQueryBuilder($viewer)
            ->andWhere('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneOwnedPrivate(int $id, User $owner): ?Manga
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.owner = :owner')
            ->andWhere('m.isPublic = :isPublic')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createVisibleQueryBuilder(User $viewer): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.categorie', 'c')
            ->addSelect('c')
            ->andWhere('(m.isPublic = :isPublic OR m.owner = :viewer)')
            ->setParameter('isPublic', true)
            ->setParameter('viewer', $viewer)
            ->distinct();
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Manga
    {
        return $this->findOneBy([
            'coverMangaUrl' => $coverUrl,
            'owner' => $owner,
        ]);
    }

    /** @return Manga[] */
    public function findOwnedPrivate(User $owner): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.owner = :owner')->andWhere('m.isPublic = false')
            ->setParameter('owner', $owner)->orderBy('m.title', 'ASC')
            ->getQuery()->getResult();
    }
}
