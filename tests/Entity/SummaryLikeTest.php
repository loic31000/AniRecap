<?php

namespace App\Tests\Entity;

use App\Entity\Summary;
use App\Entity\SummaryLike;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class SummaryLikeTest extends TestCase
{
    public function testSummaryIsPrivateByDefault(): void
    {
        $summary = new Summary();

        self::assertFalse($summary->isPublic());
        self::assertNull($summary->getPublishedAt());
        self::assertTrue($summary->setIsPublic(true)->isPublic());
        $publishedAt = new \DateTimeImmutable();
        self::assertSame($publishedAt, $summary->setPublishedAt($publishedAt)->getPublishedAt());
    }

    public function testLikeKeepsItsUserSummaryAndCreationDate(): void
    {
        $before = new \DateTimeImmutable();
        $user = new User();
        $summary = new Summary();
        $like = (new SummaryLike())->setUser($user)->setSummary($summary);

        self::assertSame($user, $like->getUser());
        self::assertSame($summary, $like->getSummary());
        self::assertGreaterThanOrEqual($before, $like->getCreatedAt());
    }
}
