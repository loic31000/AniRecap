<?php

namespace App\Repository;

use App\Entity\Summary;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Summary>
 */
class SummaryRepository extends ServiceEntityRepository
{
    private const EDITABLE_PARENT_ASSOCIATIONS = ['anime', 'manga', 'season', 'episode', 'tome', 'chapitre'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Summary::class);
    }

    /**
     * @return Summary[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.anime', 'a')
            ->leftJoin('s.manga', 'm')
            ->leftJoin('s.season', 'season')
            ->leftJoin('season.anime', 'sa')
            ->leftJoin('s.episode', 'e')
            ->leftJoin('e.season', 'es')
            ->leftJoin('es.anime', 'ea')
            ->leftJoin('s.tome', 't')
            ->leftJoin('t.manga', 'tm')
            ->leftJoin('s.chapitre', 'c')
            ->leftJoin('c.manga', 'cm')
            ->addSelect('a', 'm', 'season', 'sa', 'e', 'es', 'ea', 't', 'tm', 'c', 'cm')
            ->andWhere('s.user = :user')
            ->andWhere('(a.id IS NULL OR a.isPublic = :isPublic OR a.owner = :user)')
            ->andWhere('(m.id IS NULL OR m.isPublic = :isPublic OR m.owner = :user)')
            ->andWhere('(season.id IS NULL OR sa.isPublic = :isPublic OR sa.owner = :user)')
            ->andWhere('(e.id IS NULL OR ea.isPublic = :isPublic OR ea.owner = :user)')
            ->andWhere('(t.id IS NULL OR tm.isPublic = :isPublic OR tm.owner = :user)')
            ->andWhere('(c.id IS NULL OR cm.isPublic = :isPublic OR cm.owner = :user)')
            ->setParameter('user', $user)
            ->setParameter('isPublic', true)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneOwned(int $id, User $owner): ?Summary
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.anime', 'a')->addSelect('a')
            ->leftJoin('s.manga', 'm')->addSelect('m')
            ->leftJoin('s.season', 'season')->addSelect('season')
            ->leftJoin('season.anime', 'sa')->addSelect('sa')
            ->leftJoin('s.episode', 'e')->addSelect('e')
            ->leftJoin('e.season', 'es')->addSelect('es')
            ->leftJoin('es.anime', 'ea')->addSelect('ea')
            ->leftJoin('s.tome', 't')->addSelect('t')
            ->leftJoin('t.manga', 'tm')->addSelect('tm')
            ->leftJoin('s.chapitre', 'c')->addSelect('c')
            ->leftJoin('c.manga', 'cm')->addSelect('cm')
            ->andWhere('s.id = :id')
            ->andWhere('s.user = :owner')
            ->andWhere('(a.id IS NULL OR a.isPublic = :isPublic OR a.owner = :owner)')
            ->andWhere('(m.id IS NULL OR m.isPublic = :isPublic OR m.owner = :owner)')
            ->andWhere('(season.id IS NULL OR sa.isPublic = :isPublic OR sa.owner = :owner)')
            ->andWhere('(e.id IS NULL OR ea.isPublic = :isPublic OR ea.owner = :owner)')
            ->andWhere('(t.id IS NULL OR tm.isPublic = :isPublic OR tm.owner = :owner)')
            ->andWhere('(c.id IS NULL OR cm.isPublic = :isPublic OR cm.owner = :owner)')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<Summary> */
    public function findPreviewByUser(User $owner, int $limit = 4): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.anime', 'a')->addSelect('a')
            ->leftJoin('s.manga', 'm')->addSelect('m')
            ->leftJoin('s.season', 'season')->addSelect('season')
            ->leftJoin('season.anime', 'sa')->addSelect('sa')
            ->leftJoin('s.episode', 'episode')->addSelect('episode')
            ->leftJoin('episode.season', 'es')->addSelect('es')
            ->leftJoin('es.anime', 'ea')->addSelect('ea')
            ->leftJoin('s.tome', 'tome')->addSelect('tome')
            ->leftJoin('tome.manga', 'tm')->addSelect('tm')
            ->leftJoin('s.chapitre', 'chapitre')->addSelect('chapitre')
            ->leftJoin('chapitre.manga', 'cm')->addSelect('cm')
            ->andWhere('s.user = :owner')
            ->andWhere('(a.id IS NULL OR a.isPublic = :isPublic OR a.owner = :owner)')
            ->andWhere('(m.id IS NULL OR m.isPublic = :isPublic OR m.owner = :owner)')
            ->andWhere('(season.id IS NULL OR sa.isPublic = :isPublic OR sa.owner = :owner)')
            ->andWhere('(episode.id IS NULL OR ea.isPublic = :isPublic OR ea.owner = :owner)')
            ->andWhere('(tome.id IS NULL OR tm.isPublic = :isPublic OR tm.owner = :owner)')
            ->andWhere('(chapitre.id IS NULL OR cm.isPublic = :isPublic OR cm.owner = :owner)')
            ->setParameter('owner', $owner)
            ->setParameter('isPublic', true)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function synchronizeOwnedForParent(
        string $association,
        object $parent,
        User $owner,
        string $content,
        ?string $title = null,
        ?string $spoilerLevel = null,
    ): int {
        if (!in_array($association, self::EDITABLE_PARENT_ASSOCIATIONS, true)) {
            throw new \InvalidArgumentException('Association Summary non prise en charge.');
        }

        $qb = $this->createQueryBuilder('summary')
            ->update()
            ->set('summary.content', ':content')
            ->andWhere(sprintf('summary.%s = :parent', $association))
            ->andWhere('summary.user = :owner')
            ->setParameter('content', $content)
            ->setParameter('parent', $parent)
            ->setParameter('owner', $owner);

        if ($title !== null) {
            $qb->set('summary.title', ':title')->setParameter('title', $title);
        }
        if ($spoilerLevel !== null) {
            $qb->set('summary.spoilerLevel', ':spoilerLevel')->setParameter('spoilerLevel', $spoilerLevel);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * @param list<int> $animeIds
     * @param list<int> $mangaIds
     *
     * @return array{anime: array<int, array<string, mixed>>, manga: array<int, array<string, mixed>>}
     */
    public function findRootManagementStates(User $owner, array $animeIds, array $mangaIds): array
    {
        $animeIds = array_values(array_unique(array_map('intval', $animeIds)));
        $mangaIds = array_values(array_unique(array_map('intval', $mangaIds)));
        $states = ['anime' => [], 'manga' => []];
        if ($animeIds === [] && $mangaIds === []) {
            return $states;
        }

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.anime', 'a')->addSelect('a')
            ->leftJoin('s.season', 'season')->addSelect('season')
            ->leftJoin('season.anime', 'sa')->addSelect('sa')
            ->leftJoin('s.episode', 'episode')->addSelect('episode')
            ->leftJoin('episode.season', 'es')->addSelect('es')
            ->leftJoin('es.anime', 'ea')->addSelect('ea')
            ->leftJoin('s.manga', 'm')->addSelect('m')
            ->leftJoin('s.tome', 'tome')->addSelect('tome')
            ->leftJoin('tome.manga', 'tm')->addSelect('tm')
            ->leftJoin('s.chapitre', 'chapitre')->addSelect('chapitre')
            ->leftJoin('chapitre.manga', 'cm')->addSelect('cm')
            ->andWhere('s.user = :owner')
            ->setParameter('owner', $owner);

        $rootConditions = $qb->expr()->orX();
        if ($animeIds !== []) {
            $rootConditions->add('a.id IN (:animeIds)');
            $rootConditions->add('sa.id IN (:animeIds)');
            $rootConditions->add('ea.id IN (:animeIds)');
            $qb->setParameter('animeIds', $animeIds);
        }
        if ($mangaIds !== []) {
            $rootConditions->add('m.id IN (:mangaIds)');
            $rootConditions->add('tm.id IN (:mangaIds)');
            $rootConditions->add('cm.id IN (:mangaIds)');
            $qb->setParameter('mangaIds', $mangaIds);
        }

        /** @var list<Summary> $summaries */
        $summaries = $qb
            ->andWhere($rootConditions)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($summaries as $summary) {
            $anime = $summary->getAnime()
                ?? $summary->getSeason()?->getAnime()
                ?? $summary->getEpisode()?->getSeason()?->getAnime();
            $manga = $summary->getManga()
                ?? $summary->getTome()?->getManga()
                ?? $summary->getChapitre()?->getManga();
            $type = $anime !== null ? 'anime' : 'manga';
            $root = $anime ?? $manga;
            if ($root === null || !in_array($root->getId(), $type === 'anime' ? $animeIds : $mangaIds, true)) {
                continue;
            }

            $id = $root->getId();
            $states[$type][$id] ??= ['count' => 0, 'summaries' => []];
            ++$states[$type][$id]['count'];
            $states[$type][$id]['summaries'][] = $this->managementEntry($summary, $owner, $type, $id);
        }

        foreach ($states as $type => &$typeStates) {
            foreach ($typeStates as $id => &$state) {
                if ($state['count'] === 1) {
                    $state += $state['summaries'][0];
                } else {
                    $state['manageUrlParameters'] = ['type' => $type, 'id' => $id];
                }
                unset($state['summaries']);
            }
            unset($state);
        }
        unset($typeStates);

        return $states;
    }

    /** @return array{summaryId: int, editRoute: string, editId: int} */
    private function managementEntry(Summary $summary, User $owner, string $rootType, int $rootId): array
    {
        $parent = $summary->getAnime()
            ?? $summary->getManga()
            ?? $summary->getSeason()
            ?? $summary->getEpisode()
            ?? $summary->getTome()
            ?? $summary->getChapitre();

        $directUserMatches = !is_object($parent)
            || !method_exists($parent, 'getUser')
            || $parent->getUser()?->getId() === $owner->getId();
        if ($parent?->getOwner()?->getId() !== $owner->getId() || !$directUserMatches) {
            return ['summaryId' => $summary->getId(), 'editRoute' => 'app_summary_edit', 'editId' => $summary->getId()];
        }

        [$route, $id] = match (true) {
            $summary->getAnime() !== null => ['app_forms_anime_edit', $rootId],
            $summary->getManga() !== null => ['app_forms_manga_edit', $rootId],
            $summary->getSeason() !== null => ['app_forms_season_edit', $summary->getSeason()->getId()],
            $summary->getEpisode() !== null => ['app_forms_episode_edit', $summary->getEpisode()->getId()],
            $summary->getTome() !== null => ['app_forms_tome_edit', $summary->getTome()->getId()],
            $summary->getChapitre() !== null => ['app_forms_chapitre_edit', $summary->getChapitre()->getId()],
            default => ['app_summary_edit', $summary->getId()],
        };

        return ['summaryId' => $summary->getId(), 'editRoute' => $route, 'editId' => $id];
    }
}
