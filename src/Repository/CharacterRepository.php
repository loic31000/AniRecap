<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Anime;
use App\Entity\Manga;
use App\Entity\Season;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function findOneOwned(int $id, User $owner): ?Character
    {
        return $this->createOwnedQueryBuilder($owner)
            ->andWhere('c.id = :id')->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();
    }

    /** @return Character[] */
    public function findOwnedByAnime(Anime $anime, User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)->innerJoin('c.animes', 'filterAnime')
            ->andWhere('filterAnime = :anime')->setParameter('anime', $anime)->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    /** @return Character[] */
    public function findOwnedBySeason(Season $season, User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)->innerJoin('c.seasons', 'filterSeason')
            ->andWhere('filterSeason = :season')->setParameter('season', $season)->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    /** @return Character[] */
    public function findOwnedByManga(Manga $manga, User $owner): array
    {
        return $this->createOwnedQueryBuilder($owner)->innerJoin('c.mangas', 'filterManga')
            ->andWhere('filterManga = :manga')->setParameter('manga', $manga)->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    private function createOwnedQueryBuilder(User $owner): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.animes', 'a')->addSelect('a')
            ->leftJoin('c.mangas', 'm')->addSelect('m')
            ->andWhere('c.owner = :owner')->setParameter('owner', $owner)->distinct();
    }

    //    /**
    //     * @return Character[] Returns an array of Character objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Character
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
