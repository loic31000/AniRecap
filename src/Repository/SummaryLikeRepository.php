<?php

namespace App\Repository;

use App\Entity\SummaryLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SummaryLike> */
final class SummaryLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Connection $connection)
    {
        parent::__construct($registry, SummaryLike::class);
    }

    /** @param list<int> $summaryIds @return array<int, array{likeCount: int, likedByViewer: bool}> */
    public function findStates(array $summaryIds, User $viewer): array
    {
        $summaryIds = array_values(array_unique(array_map('intval', $summaryIds)));
        if ($summaryIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('summaryLike')
            ->select('IDENTITY(summaryLike.summary) AS summaryId')
            ->addSelect('COUNT(summaryLike.id) AS likeCount')
            ->addSelect('MAX(CASE WHEN summaryLike.user = :viewer THEN 1 ELSE 0 END) AS likedByViewer')
            ->andWhere('summaryLike.summary IN (:summaryIds)')
            ->setParameter('viewer', $viewer)
            ->setParameter('summaryIds', $summaryIds)
            ->groupBy('summaryLike.summary')
            ->getQuery()->getArrayResult();

        $states = [];
        foreach ($summaryIds as $id) {
            $states[$id] = ['likeCount' => 0, 'likedByViewer' => false];
        }
        foreach ($rows as $row) {
            $states[(int) $row['summaryId']] = [
                'likeCount' => (int) $row['likeCount'],
                'likedByViewer' => (bool) $row['likedByViewer'],
            ];
        }

        return $states;
    }

    public function removeForUserAndSummary(User $user, int $summaryId): int
    {
        return $this->createQueryBuilder('summaryLike')->delete()
            ->andWhere('summaryLike.user = :user')
            ->andWhere('summaryLike.summary = :summary')
            ->setParameter('user', $user)->setParameter('summary', $summaryId)
            ->getQuery()->execute();
    }

    /** @return list<array{type: string, id: int, score: int}> */
    public function findPopularRoots(array $filters, int $limit = 5): array
    {
        $parameters = [];
        $animeWhere = ['s.is_public = 1', 'a.is_public = 1', 'sl.user_id <> s.user_id'];
        $mangaWhere = ['s.is_public = 1', 'm.is_public = 1', 'sl.user_id <> s.user_id'];
        if (($filters['type'] ?? 'all') === 'anime') { $mangaWhere[] = '1 = 0'; }
        if (($filters['type'] ?? 'all') === 'manga') { $animeWhere[] = '1 = 0'; }
        if (($filters['q'] ?? '') !== '') {
            $animeWhere[] = "(LOWER(a.title) LIKE :query ESCAPE '!' OR LOWER(a.synopsis) LIKE :query ESCAPE '!' OR LOWER(a.author) LIKE :query ESCAPE '!')";
            $mangaWhere[] = "(LOWER(m.title) LIKE :query ESCAPE '!' OR LOWER(m.synopsis) LIKE :query ESCAPE '!' OR LOWER(m.author) LIKE :query ESCAPE '!')";
            $parameters['query'] = $filters['q_pattern'];
        }
        if (!empty($filters['date'])) {
            $animeWhere[] = 'a.release_date = :release_date'; $mangaWhere[] = 'm.release_date = :release_date';
            $parameters['release_date'] = $filters['date'];
        } elseif (!empty($filters['annee'])) {
            $animeWhere[] = 'a.anime_date = :year'; $mangaWhere[] = 'm.manga_date = :year';
            $parameters['year'] = (int) $filters['annee'];
        }
        $animeGenreJoin = $mangaGenreJoin = '';
        if (!empty($filters['genre'])) {
            $animeGenreJoin = ' INNER JOIN anime_categorie ac ON ac.anime_id = a.id INNER JOIN categorie cat_a ON cat_a.id = ac.categorie_id';
            $mangaGenreJoin = ' INNER JOIN manga_categorie mc ON mc.manga_id = m.id INNER JOIN categorie cat_m ON cat_m.id = mc.categorie_id';
            $animeWhere[] = 'cat_a.slug = :genre'; $mangaWhere[] = 'cat_m.slug = :genre';
            $parameters['genre'] = $filters['genre'];
        }

        $sql = sprintf("SELECT ranked.type, ranked.id, ranked.score FROM (
            SELECT 'anime' type, a.id, COUNT(sl.id) score, MAX(sl.created_at) activity
            FROM summary s INNER JOIN episode e ON e.id = s.episode_id INNER JOIN season se ON se.id = e.season_id
            INNER JOIN anime a ON a.id = se.anime_id%s INNER JOIN summary_like sl ON sl.summary_id = s.id
            WHERE %s GROUP BY a.id
            UNION ALL
            SELECT 'manga' type, m.id, COUNT(sl.id) score, MAX(sl.created_at) activity
            FROM summary s LEFT JOIN tome t ON t.id = s.tome_id LEFT JOIN chapitre c ON c.id = s.chapitre_id
            INNER JOIN manga m ON m.id = COALESCE(t.manga_id, c.manga_id)%s INNER JOIN summary_like sl ON sl.summary_id = s.id
            WHERE (s.tome_id IS NOT NULL OR s.chapitre_id IS NOT NULL) AND %s GROUP BY m.id
        ) ranked ORDER BY ranked.score DESC, ranked.activity DESC, ranked.id DESC LIMIT %d",
            $animeGenreJoin, implode(' AND ', $animeWhere), $mangaGenreJoin, implode(' AND ', $mangaWhere), max(1, $limit));

        return array_map(static fn (array $row): array => ['type' => $row['type'], 'id' => (int) $row['id'], 'score' => (int) $row['score']], $this->connection->fetchAllAssociative($sql, $parameters));
    }

    /** @return list<array{type: string, id: int, score: int}> */
    public function findRecentlyPublishedRoots(array $filters, int $limit = 5): array
    {
        $parameters = [];
        $animeWhere = ['s.is_public = 1', 's.published_at IS NOT NULL', 'a.is_public = 1'];
        $mangaWhere = ['s.is_public = 1', 's.published_at IS NOT NULL', 'm.is_public = 1'];
        if (($filters['type'] ?? 'all') === 'anime') { $mangaWhere[] = '1 = 0'; }
        if (($filters['type'] ?? 'all') === 'manga') { $animeWhere[] = '1 = 0'; }
        if (($filters['q'] ?? '') !== '') {
            $animeWhere[] = "(LOWER(a.title) LIKE :recent_query ESCAPE '!' OR LOWER(a.synopsis) LIKE :recent_query ESCAPE '!' OR LOWER(a.author) LIKE :recent_query ESCAPE '!')";
            $mangaWhere[] = "(LOWER(m.title) LIKE :recent_query ESCAPE '!' OR LOWER(m.synopsis) LIKE :recent_query ESCAPE '!' OR LOWER(m.author) LIKE :recent_query ESCAPE '!')";
            $parameters['recent_query'] = $filters['q_pattern'];
        }
        if (!empty($filters['date'])) {
            $animeWhere[] = 'a.release_date = :recent_date'; $mangaWhere[] = 'm.release_date = :recent_date';
            $parameters['recent_date'] = $filters['date'];
        } elseif (!empty($filters['annee'])) {
            $animeWhere[] = 'a.anime_date = :recent_year'; $mangaWhere[] = 'm.manga_date = :recent_year';
            $parameters['recent_year'] = (int) $filters['annee'];
        }
        $animeGenreJoin = $mangaGenreJoin = '';
        if (!empty($filters['genre'])) {
            $animeGenreJoin = ' INNER JOIN anime_categorie ac ON ac.anime_id = a.id INNER JOIN categorie cat_a ON cat_a.id = ac.categorie_id';
            $mangaGenreJoin = ' INNER JOIN manga_categorie mc ON mc.manga_id = m.id INNER JOIN categorie cat_m ON cat_m.id = mc.categorie_id';
            $animeWhere[] = 'cat_a.slug = :recent_genre'; $mangaWhere[] = 'cat_m.slug = :recent_genre';
            $parameters['recent_genre'] = $filters['genre'];
        }

        $sql = sprintf("SELECT ranked.type, ranked.id, ranked.score FROM (
            SELECT 'anime' type, a.id, COUNT(sl.id) score, MAX(s.published_at) publication
            FROM summary s INNER JOIN episode e ON e.id = s.episode_id INNER JOIN season se ON se.id = e.season_id
            INNER JOIN anime a ON a.id = se.anime_id%s LEFT JOIN summary_like sl ON sl.summary_id = s.id AND sl.user_id <> s.user_id WHERE %s GROUP BY a.id
            UNION ALL
            SELECT 'manga' type, m.id, COUNT(sl.id) score, MAX(s.published_at) publication
            FROM summary s LEFT JOIN tome t ON t.id = s.tome_id LEFT JOIN chapitre c ON c.id = s.chapitre_id
            INNER JOIN manga m ON m.id = COALESCE(t.manga_id, c.manga_id)%s LEFT JOIN summary_like sl ON sl.summary_id = s.id AND sl.user_id <> s.user_id
            WHERE (s.tome_id IS NOT NULL OR s.chapitre_id IS NOT NULL) AND %s GROUP BY m.id
        ) ranked ORDER BY ranked.publication DESC, ranked.id DESC LIMIT %d",
            $animeGenreJoin, implode(' AND ', $animeWhere), $mangaGenreJoin, implode(' AND ', $mangaWhere), max(1, $limit));

        return array_map(static fn (array $row): array => ['type' => $row['type'], 'id' => (int) $row['id'], 'score' => (int) $row['score']], $this->connection->fetchAllAssociative($sql, $parameters));
    }
}
