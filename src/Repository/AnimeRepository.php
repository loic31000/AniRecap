<?php

namespace App\Repository;

use App\Entity\Anime;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return Anime[]
     */
    public function findVisibleTo(User $viewer): array
    {
        return $this->createVisibleQueryBuilder($viewer)
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Anime[]
     */
    public function searchVisibleTo(array $filters, User $viewer): array
    {
        $qb = $this->createVisibleQueryBuilder($viewer);

        if (!empty($filters['q'])) {
            $qb->andWhere("(LOWER(a.title) LIKE :q ESCAPE '!' OR LOWER(a.synopsis) LIKE :q ESCAPE '!' OR LOWER(a.author) LIKE :q ESCAPE '!')")
                ->setParameter('q', $filters['q_pattern']);
        }

        if (!empty($filters['genre'])) {
            $qb->andWhere('c.slug = :genre')
                ->setParameter('genre', $filters['genre']);
        }

        if (!empty($filters['date'])) {
            $qb->andWhere('a.releaseDate = :releaseDate')
                ->setParameter('releaseDate', new \DateTimeImmutable($filters['date']));
        } elseif (!empty($filters['annee'])) {
            $qb->andWhere('a.animeDate = :annee')
                ->setParameter('annee', (int) $filters['annee']);
        }

        return $qb
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Anime[] */
    public function searchPublic(array $filters): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')->addSelect('c')
            ->andWhere('a.isPublic = true')->distinct();
        if (!empty($filters['q'])) {
            $qb->andWhere("(LOWER(a.title) LIKE :q ESCAPE '!' OR LOWER(a.synopsis) LIKE :q ESCAPE '!' OR LOWER(a.author) LIKE :q ESCAPE '!')")
                ->setParameter('q', $filters['q_pattern']);
        }
        if (!empty($filters['genre'])) { $qb->andWhere('c.slug = :genre')->setParameter('genre', $filters['genre']); }
        if (!empty($filters['date'])) { $qb->andWhere('a.releaseDate = :releaseDate')->setParameter('releaseDate', new \DateTimeImmutable($filters['date'])); }
        elseif (!empty($filters['annee'])) { $qb->andWhere('a.animeDate = :annee')->setParameter('annee', (int) $filters['annee']); }
        return $qb->orderBy('a.title', 'ASC')->getQuery()->getResult();
    }

    public function findOneVisibleTo(int $id, User $viewer): ?Anime
    {
        return $this->createVisibleQueryBuilder($viewer)
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createVisibleQueryBuilder(User $viewer): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c')
            ->andWhere('(a.isPublic = :isPublic OR a.owner = :viewer)')
            ->setParameter('isPublic', true)
            ->setParameter('viewer', $viewer)
            ->distinct();
    }

    /**
     * @return Anime[]
     */
    public function findPublic(): array
    {
        return $this->findBy(['isPublic' => true], ['title' => 'ASC']);
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Anime
    {
        return $this->findOneBy([
            'coverAnimeUrl' => $coverUrl,
            'owner' => $owner,
        ]);
    }

    public function createOwnedPrivateQueryBuilder(User $owner): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.owner = :owner')
            ->andWhere('a.isPublic = :isPublic')
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', false)
            ->orderBy('a.title', 'ASC');
    }

    public function createOwnedQueryBuilder(User $owner): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('a.title', 'ASC');
    }

    /** @return Anime[] */
    public function findOwnedPrivate(User $owner): array
    {
        return $this->createOwnedPrivateQueryBuilder($owner)->getQuery()->getResult();
    }
}
