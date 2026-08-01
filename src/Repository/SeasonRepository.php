<?php

namespace App\Repository;

use App\Entity\Anime;
use App\Entity\Season;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Season>
 */
class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    public function findOneOwned(int $id, User $owner): ?Season
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.anime', 'a')
            ->leftJoin('s.categorie', 'c')
            ->addSelect('a', 'c')
            ->andWhere('s.id = :id')
            ->andWhere('a.owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Season[] */
    public function findOwnedForCharacterSelection(User $owner): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.anime', 'a')->addSelect('a')
            ->andWhere('a.owner = :owner')->andWhere('a.isPublic = false')
            ->setParameter('owner', $owner)
            ->orderBy('a.title', 'ASC')->addOrderBy('s.number', 'ASC')
            ->getQuery()->getResult();
    }

    public function findFirstPublic(): ?Season
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.anime', 'a')
            ->leftJoin('s.categorie', 'c')
            ->addSelect('a', 'c')
            ->andWhere('a.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneOwnedByCoverUrl(string $coverUrl, User $owner): ?Season
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.anime', 'a')
            ->andWhere('s.coverSeasonUrl = :coverUrl')
            ->andWhere('a.owner = :owner')
            ->setParameter('coverUrl', $coverUrl)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function numberExistsForAnime(Anime $anime, int $number, ?int $excludedSeasonId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.anime = :anime')
            ->andWhere('s.number = :number')
            ->setParameter('anime', $anime)
            ->setParameter('number', $number);

        if ($excludedSeasonId !== null) {
            $queryBuilder
                ->andWhere('s.id != :excludedSeasonId')
                ->setParameter('excludedSeasonId', $excludedSeasonId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
