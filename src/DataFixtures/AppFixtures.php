<?php

namespace App\DataFixtures;

use App\Entity\Anime;
use App\Entity\Categorie;
use App\Entity\Character;
use App\Entity\Diaporama;
use App\Entity\Episode;
use App\Entity\Favorite;
use App\Entity\Manga;
use App\Entity\Season;
use App\Entity\Summary;
use App\Entity\User;
use App\Enum\SpoilerLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->setPasswordHash(password_hash('admin123', PASSWORD_DEFAULT));
        $manager->persist($user);

        $categorieAction = new Categorie();
        $categorieAction->setName('ACTION');
        $categorieAction->setSlug('action-adventure');
        $manager->persist($categorieAction);

        $categorieRomance = new Categorie();
        $categorieRomance->setName('ROMANCE');
        $categorieRomance->setSlug('romance');
        $manager->persist($categorieRomance);

        $anime = new Anime();
        $anime->setTitle('KATEKYO HITMAN REBORN');
        $anime->setSynopsis('Un lycéeen sans talent voit sa vie basculer avec l\'arrivée d\'un tueur à gages nommé Reborn.');
        $anime->setCoverAnimeUrl('https://images.unsplash.com/photo-1578632749014-ca77efd052eb?auto=format&fit=crop&w=800&q=80');
        $anime->setType('Shonen');
        $anime->setStatus('Terminé');
        $anime->setAuthor('Akira Amano');
        $anime->setAnimeDate(2006);
        $anime->addCategorie($categorieAction);
        $manager->persist($anime);

        $animeDemon = new Anime();
        $animeDemon->setTitle('DEMON SLAYER');
        $animeDemon->setSynopsis('Tanjiro cherche à sauver sa sœur transformée en démon.');
        $animeDemon->setCoverAnimeUrl('https://images.unsplash.com/photo-1578632749014-ca77efd052eb?auto=format&fit=crop&w=800&q=80');
        $animeDemon->setType('Shonen');
        $animeDemon->setStatus('En cours');
        $animeDemon->setAuthor('Koyoharu Gotouge');
        $animeDemon->setAnimeDate(2019);
        $animeDemon->addCategorie($categorieAction);
        $animeDemon->addCategorie($categorieRomance);
        $manager->persist($animeDemon);

        $season = new Season();
        $season->setTitle('Saison 1');
        $season->setSynopsis('Première saison de Reborn!');
        $season->setCoverSeasonUrl('/images/coverAnime.png');
        $season->setType('TV');
        $season->setStatus('Terminé');
        $season->setAuthor('Akira Amano');
        $season->setSeasonDate(2006);
        $season->setAnime($anime);
        $season->addCategorie($categorieAction);
        $manager->persist($season);

        $character = new Character();
        $character->setName('Gintoki');
        $character->setDescription('Un adolescent maladroit qui découvre qu\'il est destiné à devenir le dixième boss de la Vongola.');
        $character->setImageUrl('/images/coverAnime.png');
        $character->setSpoilerLevel(SpoilerLevel::Aucun);
        $character->addAnime($anime);
        $character->addSeason($season);
        $manager->persist($character);

        $manga = new Manga();
        $manga->setTitle('ONE PIECE');
        $manga->setSynopsis('Luffy rêve de devenir le roi des pirates.');
        $manga->setCoverMangaUrl('https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=800&q=80');
        $manga->setType('Manga');
        $manga->setMangaDate(1997);
        $manga->setStatus('En cours');
        $manga->setAuthor('Eiichiro Oda');
        $manga->addCategorie($categorieAction);
        $manager->persist($manga);

        $mangaDragon = new Manga();
        $mangaDragon->setTitle('DRAGON BALL SUPER');
        $mangaDragon->setSynopsis('Goku et ses amis défendent l’univers face à de nouveaux rivaux.');
        $mangaDragon->setCoverMangaUrl('https://images.unsplash.com/photo-1518791841217-8f162f1e1131?auto=format&fit=crop&w=800&q=80');
        $mangaDragon->setType('Manga');
        $mangaDragon->setMangaDate(2015);
        $mangaDragon->setStatus('En cours');
        $mangaDragon->setAuthor('Akira Toriyama');
        $mangaDragon->addCategorie($categorieAction);
        $mangaDragon->addCategorie($categorieRomance);
        $manager->persist($mangaDragon);

        $episode = new Episode();
        $episode->setTitle('Reborn arrive !');
        $episode->setSynopsis('Reborn débarque chez Tsuna et bouleverse sa vie.');
        $episode->setCoverEpisodeUrl('images/tsuna.jpg');
        $episode->setType('Episode');
        $episode->setStatus('Sorti');
        $episode->setAuthor('Akira Amano');
        $episode->setEpisodeDate(2006);
        $episode->setSpoilerLevel(SpoilerLevel::Aucun);
        $episode->setSeason($season);
        $episode->setUser($user);
        $episode->addCategorie($categorieAction);
        $manager->persist($episode);

        $diaporama = new Diaporama();
        $diaporama->setTitle('家庭教師ヒットマン REBORN!');
        $diaporama->setContent(
            '<a href="https://fr.wikipedia.org/wiki/Reborn!" target="_blank" rel="noopener">'
            . 'https://fr.wikipedia.org/wiki/Reborn!</a>'
            . '<p>Dernier dans tous les domaines, y compris en charisme et la chance, '
            . 'il n\'a rien pour plaire et il le sait. Cependant, sa triste vie va basculer '
            . 'dans l\'incroyable le jour où un petit bonhomme du nom de Reborn va détruire '
            . 'son train-train quotidien pour faire de lui le prochain parrain de la famille '
            . 'Vongola (une grande famille de la '
            . '<a href="https://fr.wikipedia.org/wiki/Mafia" target="_blank" rel="noopener">mafia</a>'
            . ' italienne).</p>'
        );
        $diaporama->setUser($user);
        $diaporama->setEpisode($episode);
        $diaporama->addCategorie($categorieAction);
        $manager->persist($diaporama);

        $favoriteAnime = new Favorite();
        $favoriteAnime->setUser($user);
        $favoriteAnime->setAnime($anime);
        $favoriteAnime->setCreatedAt(new \DateTime('2026-07-10'));
        $manager->persist($favoriteAnime);

        $favoriteSeason = new Favorite();
        $favoriteSeason->setUser($user);
        $favoriteSeason->setSeason($season);
        $favoriteSeason->setCreatedAt(new \DateTime('2026-07-11'));
        $manager->persist($favoriteSeason);

        $favoriteManga = new Favorite();
        $favoriteManga->setUser($user);
        $favoriteManga->setManga($manga);
        $favoriteManga->setCreatedAt(new \DateTime('2026-07-12'));
        $manager->persist($favoriteManga);

        $summaryAnime = new Summary();
        $summaryAnime->setTitle('Résumé Reborn');
        $summaryAnime->setContent('Un résumé de la première saison de Reborn.');
        $summaryAnime->setUser($user);
        $summaryAnime->setAnime($anime);
        $manager->persist($summaryAnime);

        $summaryManga = new Summary();
        $summaryManga->setTitle('Résumé One Piece');
        $summaryManga->setContent('Un résumé de l’aventure de Luffy.');
        $summaryManga->setUser($user);
        $summaryManga->setManga($manga);
        $manager->persist($summaryManga);

        $manager->flush();
    }
}
