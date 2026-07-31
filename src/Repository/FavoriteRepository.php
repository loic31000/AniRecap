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
            ->leftJoin('a.categories', 'ac')
            ->leftJoin('a.seasons', 'aseason')
            ->leftJoin('aseason.episodes', 'aepisode')
            ->leftJoin('a.votes', 'av')
            ->leftJoin('f.manga', 'm')
            ->leftJoin('m.categorie', 'mc')
            ->leftJoin('m.tomes', 'mt')
            ->leftJoin('m.chapitres', 'mch')
            ->leftJoin('m.votes', 'mv')
            ->leftJoin('f.season', 's')
            ->leftJoin('s.anime', 'sa')
            ->leftJoin('sa.categories', 'sac')
            ->leftJoin('sa.seasons', 'saseason')
            ->leftJoin('saseason.episodes', 'saepisode')
            ->leftJoin('sa.votes', 'sav')
            ->leftJoin('f.episode', 'e')
            ->leftJoin('e.season', 'es')
            ->leftJoin('es.anime', 'ea')
            ->leftJoin('ea.categories', 'eac')
            ->leftJoin('ea.seasons', 'easeason')
            ->leftJoin('easeason.episodes', 'eaepisode')
            ->leftJoin('ea.votes', 'eav')
            ->leftJoin('f.tome', 't')
            ->leftJoin('t.manga', 'tm')
            ->leftJoin('tm.categorie', 'tmc')
            ->leftJoin('tm.tomes', 'tmt')
            ->leftJoin('tm.chapitres', 'tmch')
            ->leftJoin('tm.votes', 'tmv')
            ->leftJoin('f.chapitre', 'ch')
            ->leftJoin('ch.manga', 'chm')
            ->leftJoin('chm.categorie', 'chmc')
            ->leftJoin('chm.tomes', 'chmt')
            ->leftJoin('chm.chapitres', 'chmch')
            ->leftJoin('chm.votes', 'chmv')
            ->addSelect(
                'a',
                'ac',
                'aseason',
                'aepisode',
                'av',
                'm',
                'mc',
                'mt',
                'mch',
                'mv',
                's',
                'sa',
                'sac',
                'saseason',
                'saepisode',
                'sav',
                'e',
                'es',
                'ea',
                'eac',
                'easeason',
                'eaepisode',
                'eav',
                't',
                'tm',
                'tmc',
                'tmt',
                'tmch',
                'tmv',
                'ch',
                'chm',
                'chmc',
                'chmt',
                'chmch',
                'chmv',
            )
            ->andWhere('f.user = :user')
            ->andWhere('(a.id IS NULL OR a.isPublic = :isPublic OR a.owner = :user)')
            ->andWhere('(m.id IS NULL OR m.isPublic = :isPublic OR m.owner = :user)')
            ->andWhere('(s.id IS NULL OR sa.isPublic = :isPublic OR sa.owner = :user)')
            ->andWhere('(e.id IS NULL OR ea.isPublic = :isPublic OR ea.owner = :user)')
            ->andWhere('(t.id IS NULL OR tm.isPublic = :isPublic OR tm.owner = :user)')
            ->andWhere('(ch.id IS NULL OR chm.isPublic = :isPublic OR chm.owner = :user)')
            ->andWhere('(a.id IS NOT NULL OR m.id IS NOT NULL OR s.id IS NOT NULL OR e.id IS NOT NULL OR t.id IS NOT NULL OR ch.id IS NOT NULL)')
            ->setParameter('user', $user)
            ->setParameter('isPublic', true)
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('f.id', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }
}
