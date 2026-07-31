<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise la casse et les accents des 35 libellés de catégories sans modifier leurs slugs ni leurs relations.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        $this->addSql(<<<'SQL'
            UPDATE categorie
            SET name = CASE slug
                WHEN 'action' THEN 'Action'
                WHEN 'aventure' THEN 'Aventure'
                WHEN 'comedie' THEN 'Comédie'
                WHEN 'drame' THEN 'Drame'
                WHEN 'fantasy' THEN 'Fantasy'
                WHEN 'romance' THEN 'Romance'
                WHEN 'science-fiction' THEN 'Science-fiction'
                WHEN 'mystere' THEN 'Mystère'
                WHEN 'thriller' THEN 'Thriller'
                WHEN 'horreur' THEN 'Horreur'
                WHEN 'surnaturel' THEN 'Surnaturel'
                WHEN 'psychologique' THEN 'Psychologique'
                WHEN 'historique' THEN 'Historique'
                WHEN 'sport' THEN 'Sport'
                WHEN 'slice-of-life' THEN 'Tranche de vie'
                WHEN 'ecole' THEN 'École'
                WHEN 'musique' THEN 'Musique'
                WHEN 'policier' THEN 'Policier'
                WHEN 'arts-martiaux' THEN 'Arts martiaux'
                WHEN 'mecha' THEN 'Mecha'
                WHEN 'isekai' THEN 'Isekai'
                WHEN 'magie' THEN 'Magie'
                WHEN 'super-heros' THEN 'Super-héros'
                WHEN 'cyberpunk' THEN 'Cyberpunk'
                WHEN 'post-apocalyptique' THEN 'Post-apocalyptique'
                WHEN 'militaire' THEN 'Militaire'
                WHEN 'samourai' THEN 'Samouraï'
                WHEN 'cuisine' THEN 'Cuisine'
                WHEN 'jeux' THEN 'Jeux'
                WHEN 'paranormal' THEN 'Paranormal'
                WHEN 'shonen' THEN 'Shōnen'
                WHEN 'shojo' THEN 'Shōjo'
                WHEN 'seinen' THEN 'Seinen'
                WHEN 'josei' THEN 'Josei'
                WHEN 'kodomo' THEN 'Kodomo'
                ELSE name
            END
            WHERE slug IN (
                'action', 'aventure', 'comedie', 'drame', 'fantasy', 'romance',
                'science-fiction', 'mystere', 'thriller', 'horreur', 'surnaturel',
                'psychologique', 'historique', 'sport', 'slice-of-life', 'ecole',
                'musique', 'policier', 'arts-martiaux', 'mecha', 'isekai', 'magie',
                'super-heros', 'cyberpunk', 'post-apocalyptique', 'militaire',
                'samourai', 'cuisine', 'jeux', 'paranormal', 'shonen', 'shojo',
                'seinen', 'josei', 'kodomo'
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            true,
            'The previous inconsistent capitalization cannot be restored safely.',
        );
    }
}
