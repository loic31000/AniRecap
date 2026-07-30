<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729130955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un numéro obligatoire et unique par saison aux épisodes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE episode ADD number INT DEFAULT NULL');
        $this->addSql(
            'UPDATE episode e
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY season_id ORDER BY id) AS generated_number
                FROM episode
            ) ranked ON ranked.id = e.id
            SET e.number = ranked.generated_number'
        );
        $this->addSql('ALTER TABLE episode MODIFY number INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EPISODE_SEASON_NUMBER ON episode (season_id, number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_EPISODE_SEASON_NUMBER ON episode');
        $this->addSql('ALTER TABLE episode DROP number');
    }
}
