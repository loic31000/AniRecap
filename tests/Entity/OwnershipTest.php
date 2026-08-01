<?php

namespace App\Tests\Entity;

use App\Entity\Anime;
use App\Entity\Chapitre;
use App\Entity\Diaporama;
use App\Entity\Episode;
use App\Entity\Manga;
use App\Entity\Season;
use App\Entity\Tome;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class OwnershipTest extends TestCase
{
    public function testAnimeChildrenDeriveOwnershipFromTheRootAnime(): void
    {
        $owner = new User();
        $anime = (new Anime())->setOwner($owner);
        $season = (new Season())->setAnime($anime);
        $episode = (new Episode())->setSeason($season)->setUser(new User());

        self::assertSame($owner, $season->getOwner());
        self::assertSame($owner, $episode->getOwner());
    }

    public function testMangaChildrenDeriveOwnershipFromTheRootManga(): void
    {
        $owner = new User();
        $manga = (new Manga())->setOwner($owner);
        $tome = (new Tome())->setManga($manga)->setUser(new User());
        $chapitre = (new Chapitre())->setManga($manga)->setUser(new User());

        self::assertSame($owner, $tome->getOwner());
        self::assertSame($owner, $chapitre->getOwner());
    }

    public function testDiaporamaOwnershipIsItsUser(): void
    {
        $owner = new User();
        $diaporama = (new Diaporama())->setUser($owner);

        self::assertSame($owner, $diaporama->getOwner());
    }

    public function testDiaporamaRejectsAnUnknownSourceType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Diaporama())->setSourceType('unknown');
    }
}
