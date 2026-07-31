<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @param int[] $animeIds
     * @param int[] $mangaIds
     *
     * @return array{anime: array<int, bool>, manga: array<int, bool>}
     */
    public function findRootFavoriteStates(User $user, array $animeIds, array $mangaIds): array
    {
        $animeIds = array_values(array_unique(array_map('intval', $animeIds)));
        $mangaIds = array_values(array_unique(array_map('intval', $mangaIds)));
        $states = ['anime' => [], 'manga' => []];

        if ($animeIds === [] && $mangaIds === []) {
            return $states;
        }

        $qb = $this->createRootResolutionQueryBuilder($user);
        $conditions = $qb->expr()->orX();

        if ($animeIds !== []) {
            $conditions->add('a.id IN (:animeIds)');
            $conditions->add('sa.id IN (:animeIds)');
            $conditions->add('ea.id IN (:animeIds)');
            $qb->setParameter('animeIds', $animeIds);
        }

        if ($mangaIds !== []) {
            $conditions->add('m.id IN (:mangaIds)');
            $conditions->add('tm.id IN (:mangaIds)');
            $conditions->add('cm.id IN (:mangaIds)');
            $qb->setParameter('mangaIds', $mangaIds);
        }

        /** @var Favorite[] $favorites */
        $favorites = $qb
            ->andWhere($conditions)
            ->getQuery()
            ->getResult();

        foreach ($favorites as $favorite) {
            $animeId = $favorite->getAnime()?->getId()
                ?? $favorite->getSeason()?->getAnime()?->getId()
                ?? $favorite->getEpisode()?->getSeason()?->getAnime()?->getId();
            if ($animeId !== null && in_array($animeId, $animeIds, true)) {
                $states['anime'][$animeId] = true;
            }

            $mangaId = $favorite->getManga()?->getId()
                ?? $favorite->getTome()?->getManga()?->getId()
                ?? $favorite->getChapitre()?->getManga()?->getId();
            if ($mangaId !== null && in_array($mangaId, $mangaIds, true)) {
                $states['manga'][$mangaId] = true;
            }
        }

        return $states;
    }

    public function rootFavoriteExists(User $user, string $type, int $id): bool
    {
        $states = $this->findRootFavoriteStates(
            $user,
            $type === 'anime' ? [$id] : [],
            $type === 'manga' ? [$id] : [],
        );

        return $states[$type][$id] ?? false;
    }

    public function removeRootFavorites(User $user, string $type, int $id): int
    {
        $qb = $this->createRootResolutionQueryBuilder($user)
            ->select('f.id');

        if ($type === 'anime') {
            $qb->andWhere('(a.id = :rootId OR sa.id = :rootId OR ea.id = :rootId)');
        } else {
            $qb->andWhere('(m.id = :rootId OR tm.id = :rootId OR cm.id = :rootId)');
        }

        $rows = $qb
            ->setParameter('rootId', $id)
            ->getQuery()
            ->getScalarResult();
        $favoriteIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);

        if ($favoriteIds === []) {
            return 0;
        }

        return $this->createQueryBuilder('favoriteToDelete')
            ->delete()
            ->andWhere('favoriteToDelete.id IN (:favoriteIds)')
            ->setParameter('favoriteIds', $favoriteIds)
            ->getQuery()
            ->execute();
    }

    private function createRootResolutionQueryBuilder(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.anime', 'a')->addSelect('a')
            ->leftJoin('f.season', 's')->addSelect('s')
            ->leftJoin('s.anime', 'sa')->addSelect('sa')
            ->leftJoin('f.episode', 'e')->addSelect('e')
            ->leftJoin('e.season', 'es')->addSelect('es')
            ->leftJoin('es.anime', 'ea')->addSelect('ea')
            ->leftJoin('f.manga', 'm')->addSelect('m')
            ->leftJoin('f.tome', 't')->addSelect('t')
            ->leftJoin('t.manga', 'tm')->addSelect('tm')
            ->leftJoin('f.chapitre', 'c')->addSelect('c')
            ->leftJoin('c.manga', 'cm')->addSelect('cm')
            ->andWhere('f.user = :favoriteOwner')
            ->setParameter('favoriteOwner', $user)
            ->distinct();
    }
}
