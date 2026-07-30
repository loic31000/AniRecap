<?php

namespace App\Repository;

use App\Entity\Diaporama;
use App\Entity\Slide;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slide>
 */
final class SlideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slide::class);
    }

    public function nextPosition(Diaporama $diaporama): int
    {
        $lastPosition = $this->createQueryBuilder('s')
            ->select('MAX(s.position)')
            ->andWhere('s.diaporama = :diaporama')
            ->setParameter('diaporama', $diaporama)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $lastPosition) + 1;
    }

    public function findOneOwnedInDiaporama(int $slideId, int $diaporamaId, User $owner): ?Slide
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.diaporama', 'd')
            ->addSelect('d')
            ->andWhere('s.id = :slideId')
            ->andWhere('d.id = :diaporamaId')
            ->andWhere('d.user = :owner')
            ->setParameter('slideId', $slideId)
            ->setParameter('diaporamaId', $diaporamaId)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
