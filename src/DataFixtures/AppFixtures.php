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
use App\Entity\Slide;
use App\Entity\Summary;
use App\Entity\User;
use App\Enum\SpoilerLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /**
     * @var array<string, array{name: string, slug: string}>
     */
    private const CATEGORIES = [
        'category-action' => ['name' => 'Action', 'slug' => 'action'],
        'category-adventure' => ['name' => 'Aventure', 'slug' => 'aventure'],
        'category-comedy' => ['name' => 'Comédie', 'slug' => 'comedie'],
        'category-drama' => ['name' => 'Drame', 'slug' => 'drame'],
        'category-fantasy' => ['name' => 'Fantasy', 'slug' => 'fantasy'],
        'category-romance' => ['name' => 'Romance', 'slug' => 'romance'],
        'category-science-fiction' => ['name' => 'Science-fiction', 'slug' => 'science-fiction'],
        'category-mystery' => ['name' => 'Mystère', 'slug' => 'mystere'],
        'category-thriller' => ['name' => 'Thriller', 'slug' => 'thriller'],
        'category-horror' => ['name' => 'Horreur', 'slug' => 'horreur'],
        'category-supernatural' => ['name' => 'Surnaturel', 'slug' => 'surnaturel'],
        'category-psychological' => ['name' => 'Psychologique', 'slug' => 'psychologique'],
        'category-historical' => ['name' => 'Historique', 'slug' => 'historique'],
        'category-sport' => ['name' => 'Sport', 'slug' => 'sport'],
        'category-slice-of-life' => ['name' => 'Tranche de vie', 'slug' => 'slice-of-life'],
        'category-school' => ['name' => 'École', 'slug' => 'ecole'],
        'category-music' => ['name' => 'Musique', 'slug' => 'musique'],
        'category-police' => ['name' => 'Policier', 'slug' => 'policier'],
        'category-martial-arts' => ['name' => 'Arts martiaux', 'slug' => 'arts-martiaux'],
        'category-mecha' => ['name' => 'Mecha', 'slug' => 'mecha'],
        'category-isekai' => ['name' => 'Isekai', 'slug' => 'isekai'],
        'category-magic' => ['name' => 'Magie', 'slug' => 'magie'],
        'category-superhero' => ['name' => 'Super-héros', 'slug' => 'super-heros'],
        'category-cyberpunk' => ['name' => 'Cyberpunk', 'slug' => 'cyberpunk'],
        'category-post-apocalyptic' => ['name' => 'Post-apocalyptique', 'slug' => 'post-apocalyptique'],
        'category-military' => ['name' => 'Militaire', 'slug' => 'militaire'],
        'category-samurai' => ['name' => 'Samouraï', 'slug' => 'samourai'],
        'category-cooking' => ['name' => 'Cuisine', 'slug' => 'cuisine'],
        'category-games' => ['name' => 'Jeux', 'slug' => 'jeux'],
        'category-paranormal' => ['name' => 'Paranormal', 'slug' => 'paranormal'],
        'category-shonen' => ['name' => 'Shōnen', 'slug' => 'shonen'],
        'category-shojo' => ['name' => 'Shōjo', 'slug' => 'shojo'],
        'category-seinen' => ['name' => 'Seinen', 'slug' => 'seinen'],
        'category-josei' => ['name' => 'Josei', 'slug' => 'josei'],
        'category-kodomo' => ['name' => 'Kodomo', 'slug' => 'kodomo'],
    ];

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->setPasswordHash(password_hash('admin123', PASSWORD_DEFAULT));
        $manager->persist($user);

        $categories = [];
        foreach (self::CATEGORIES as $reference => $data) {
            $category = (new Categorie())
                ->setName($data['name'])
                ->setSlug($data['slug']);
            $manager->persist($category);
            $this->addReference($reference, $category);
            $categories[$reference] = $category;
        }

        $anime = new Anime();
        $anime->setTitle('KATEKYO HITMAN REBORN');
        $anime->setSynopsis('Un lycéeen sans talent voit sa vie basculer avec l\'arrivée d\'un tueur à gages nommé Reborn.');
        $anime->setCoverAnimeUrl('https://images.unsplash.com/photo-1578632749014-ca77efd052eb?auto=format&fit=crop&w=800&q=80');
        $anime->setType('Shonen');
        $anime->setStatus('Terminé');
        $anime->setAuthor('Akira Amano');
        $anime->setAnimeDate(2006);
        $anime
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-comedy'])
            ->addCategorie($categories['category-supernatural'])
            ->addCategorie($categories['category-shonen']);
        $manager->persist($anime);

        $animeDemon = new Anime();
        $animeDemon->setTitle('DEMON SLAYER');
        $animeDemon->setSynopsis('Tanjiro cherche à sauver sa sœur transformée en démon.');
        $animeDemon->setCoverAnimeUrl('https://images.unsplash.com/photo-1578632749014-ca77efd052eb?auto=format&fit=crop&w=800&q=80');
        $animeDemon->setType('Shonen');
        $animeDemon->setStatus('En cours');
        $animeDemon->setAuthor('Koyoharu Gotouge');
        $animeDemon->setAnimeDate(2019);
        $animeDemon
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-adventure'])
            ->addCategorie($categories['category-fantasy'])
            ->addCategorie($categories['category-supernatural'])
            ->addCategorie($categories['category-shonen']);
        $manager->persist($animeDemon);

        $animeMecha = new Anime();
        $animeMecha->setTitle('GUNDAM SEED DESTINY');
        $animeMecha->setSynopsis('Une nouvelle génération de pilotes affronte un conflit opposant les colonies spatiales et la Terre.');
        $animeMecha->setCoverAnimeUrl('/images/coverAnime.png');
        $animeMecha->setType('Anime');
        $animeMecha->setStatus('Terminé');
        $animeMecha->setAuthor('Hajime Yatate');
        $animeMecha->setAnimeDate(2004);
        $animeMecha
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-mecha'])
            ->addCategorie($categories['category-science-fiction'])
            ->addCategorie($categories['category-military'])
            ->addCategorie($categories['category-seinen']);
        $manager->persist($animeMecha);

        $season = new Season();
        $season->setNumber(1);
        $season->setTitle('Saison 1');
        $season->setSynopsis('Première saison de Reborn!');
        $season->setCoverSeasonUrl('/images/coverAnime.png');
        $season->setType('TV');
        $season->setStatus('Terminé');
        $season->setAuthor('Akira Amano');
        $season->setSeasonDate(2006);
        $season->setAnime($anime);
        $season
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-comedy'])
            ->addCategorie($categories['category-shonen']);
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
        $manga
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-adventure'])
            ->addCategorie($categories['category-fantasy'])
            ->addCategorie($categories['category-comedy'])
            ->addCategorie($categories['category-shonen']);
        $manager->persist($manga);

        $mangaDragon = new Manga();
        $mangaDragon->setTitle('DRAGON BALL SUPER');
        $mangaDragon->setSynopsis('Goku et ses amis défendent l’univers face à de nouveaux rivaux.');
        $mangaDragon->setCoverMangaUrl('https://images.unsplash.com/photo-1518791841217-8f162f1e1131?auto=format&fit=crop&w=800&q=80');
        $mangaDragon->setType('Manga');
        $mangaDragon->setMangaDate(2015);
        $mangaDragon->setStatus('En cours');
        $mangaDragon->setAuthor('Akira Toriyama');
        $mangaDragon
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-adventure'])
            ->addCategorie($categories['category-martial-arts'])
            ->addCategorie($categories['category-science-fiction'])
            ->addCategorie($categories['category-shonen']);
        $manager->persist($mangaDragon);

        $mangaRomance = new Manga();
        $mangaRomance->setTitle('NANA');
        $mangaRomance->setSynopsis('Deux jeunes femmes portant le même prénom se rencontrent et partagent un appartement à Tokyo.');
        $mangaRomance->setCoverMangaUrl('/images/coverMangaCard.png');
        $mangaRomance->setType('Manga');
        $mangaRomance->setMangaDate(2000);
        $mangaRomance->setStatus('En pause');
        $mangaRomance->setAuthor('Ai Yazawa');
        $mangaRomance
            ->addCategorie($categories['category-romance'])
            ->addCategorie($categories['category-drama'])
            ->addCategorie($categories['category-music'])
            ->addCategorie($categories['category-slice-of-life'])
            ->addCategorie($categories['category-josei']);
        $manager->persist($mangaRomance);

        $episode = new Episode();
        $episode->setNumber(1);
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
        $episode
            ->addCategorie($categories['category-action'])
            ->addCategorie($categories['category-comedy'])
            ->addCategorie($categories['category-shonen']);
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
        $diaporama->setSourceType(Diaporama::SOURCE_ANIME);
        $diaporama->addCategorie($categories['category-action']);
        $manager->persist($diaporama);

        $slide = new Slide();
        $slide->setDiaporama($diaporama);
        $slide->setEpisode($episode);
        $slide->setTitle($diaporama->getTitle());
        $slide->setContent($diaporama->getContent());
        $slide->setPosition(1);
        $slide->setSpoilerLevel(SpoilerLevel::Aucun);
        $manager->persist($slide);

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

        $favoriteMecha = new Favorite();
        $favoriteMecha->setUser($user);
        $favoriteMecha->setAnime($animeMecha);
        $favoriteMecha->setCreatedAt(new \DateTime('2026-07-13'));
        $manager->persist($favoriteMecha);

        $favoriteRomance = new Favorite();
        $favoriteRomance->setUser($user);
        $favoriteRomance->setManga($mangaRomance);
        $favoriteRomance->setCreatedAt(new \DateTime('2026-07-14'));
        $manager->persist($favoriteRomance);

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
