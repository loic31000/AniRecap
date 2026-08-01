<?php

namespace App\Tests\Service;

use App\Service\OeuvreFilterNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class OeuvreFilterNormalizerTest extends TestCase
{
    private const GENRES = [
        ['slug' => 'action', 'name' => 'Action'],
        ['slug' => 'science-fiction', 'name' => 'Science-fiction'],
    ];

    public function testItNormalizesInvalidFilters(): void
    {
        $request = new Request(['type' => 'invalid', 'genre' => 'unknown', 'annee' => 'abc', 'date' => '2026-99-99']);

        $filters = (new OeuvreFilterNormalizer())->normalize($request, self::GENRES);

        self::assertSame('all', $filters['type']);
        self::assertNull($filters['genre']);
        self::assertNull($filters['annee']);
        self::assertNull($filters['date']);
    }

    public function testAValidDateDrivesTheYearFilter(): void
    {
        $request = new Request(['type' => 'anime', 'genre' => 'action', 'annee' => '2024', 'date' => '2026-03-12']);

        $filters = (new OeuvreFilterNormalizer())->normalize($request, self::GENRES);

        self::assertSame('anime', $filters['type']);
        self::assertSame('action', $filters['genre']);
        self::assertSame(2026, $filters['annee']);
        self::assertSame('2026-03-12', $filters['date']);
    }

    public function testSqlWildcardCharactersAreEscaped(): void
    {
        $request = new Request(['q' => '100%_Mecha!']);

        $filters = (new OeuvreFilterNormalizer())->normalize($request, self::GENRES);

        self::assertSame('%100!%!_mecha!!%', $filters['q_pattern']);
    }
}
