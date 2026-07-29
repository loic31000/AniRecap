<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729120434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la propriété et les métadonnées structurées des synopsis anime et manga.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anime ADD release_date DATE DEFAULT NULL, ADD initial_season_number INT DEFAULT NULL, ADD episode_count INT DEFAULT NULL, ADD is_public TINYINT DEFAULT 1 NOT NULL, ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anime ADD CONSTRAINT FK_130459427E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_130459427E3C61F9 ON anime (owner_id)');
        $this->addSql('ALTER TABLE manga ADD release_date DATE DEFAULT NULL, ADD tome_start INT DEFAULT NULL, ADD tome_end INT DEFAULT NULL, ADD chapter_start INT DEFAULT NULL, ADD chapter_end INT DEFAULT NULL, ADD is_public TINYINT DEFAULT 1 NOT NULL, ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E037E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_765A9E037E3C61F9 ON manga (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anime DROP FOREIGN KEY FK_130459427E3C61F9');
        $this->addSql('DROP INDEX IDX_130459427E3C61F9 ON anime');
        $this->addSql('ALTER TABLE anime DROP release_date, DROP initial_season_number, DROP episode_count, DROP is_public, DROP owner_id');
        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E037E3C61F9');
        $this->addSql('DROP INDEX IDX_765A9E037E3C61F9 ON manga');
        $this->addSql('ALTER TABLE manga DROP release_date, DROP tome_start, DROP tome_end, DROP chapter_start, DROP chapter_end, DROP is_public, DROP owner_id');
    }
}
