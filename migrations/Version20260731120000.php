<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les 35 catégories Anime/Manga de référence sans remplacer leurs relations existantes.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        // This immutable list is intentionally kept inside the migration so that
        // its historical behaviour cannot change with application fixture code.
        $this->addSql(<<<'SQL'
            INSERT INTO categorie (name, slug) VALUES
                ('Action', 'action'),
                ('Aventure', 'aventure'),
                ('Comédie', 'comedie'),
                ('Drame', 'drame'),
                ('Fantasy', 'fantasy'),
                ('Romance', 'romance'),
                ('Science-fiction', 'science-fiction'),
                ('Mystère', 'mystere'),
                ('Thriller', 'thriller'),
                ('Horreur', 'horreur'),
                ('Surnaturel', 'surnaturel'),
                ('Psychologique', 'psychologique'),
                ('Historique', 'historique'),
                ('Sport', 'sport'),
                ('Tranche de vie', 'slice-of-life'),
                ('École', 'ecole'),
                ('Musique', 'musique'),
                ('Policier', 'policier'),
                ('Arts martiaux', 'arts-martiaux'),
                ('Mecha', 'mecha'),
                ('Isekai', 'isekai'),
                ('Magie', 'magie'),
                ('Super-héros', 'super-heros'),
                ('Cyberpunk', 'cyberpunk'),
                ('Post-apocalyptique', 'post-apocalyptique'),
                ('Militaire', 'militaire'),
                ('Samouraï', 'samourai'),
                ('Cuisine', 'cuisine'),
                ('Jeux', 'jeux'),
                ('Paranormal', 'paranormal'),
                ('Shōnen', 'shonen'),
                ('Shōjo', 'shojo'),
                ('Seinen', 'seinen'),
                ('Josei', 'josei'),
                ('Kodomo', 'kodomo')
            ON DUPLICATE KEY UPDATE slug = VALUES(slug)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            true,
            'This reference-data migration is irreversible to avoid deleting categories linked to user content.',
        );
    }
}
