<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final class OeuvreFilterNormalizer
{
    /**
     * @param list<array{slug: string, name: string}> $genres
     * @return array{q: string, q_pattern: string, type: string, genre: ?string, annee: ?int, date: ?string}
     */
    public function normalize(Request $request, array $genres): array
    {
        $type = strtolower((string) $request->query->get('type', 'all'));
        if (!in_array($type, ['all', 'anime', 'manga'], true)) {
            $type = 'all';
        }

        $allowedGenres = array_column($genres, 'slug');
        $genre = (string) $request->query->get('genre', '');
        if ($genre === '' || !in_array($genre, $allowedGenres, true)) {
            $genre = null;
        }

        $date = $this->validDate((string) $request->query->get('date', ''));
        $year = $date !== null ? (int) substr($date, 0, 4) : $this->validYear($request->query->get('annee'));

        $query = mb_substr(trim((string) $request->query->get('q', '')), 0, 120);

        return [
            'q' => $query,
            'q_pattern' => '%' . strtr(mb_strtolower($query), ['!' => '!!', '%' => '!%', '_' => '!_']) . '%',
            'type' => $type,
            'genre' => $genre,
            'annee' => $year,
            'date' => $date,
        ];
    }

    private function validDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }

    private function validYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $year = filter_var($value, FILTER_VALIDATE_INT);
        $maximum = (int) date('Y') + 5;

        return $year !== false && $year >= 1900 && $year <= $maximum ? $year : null;
    }
}
