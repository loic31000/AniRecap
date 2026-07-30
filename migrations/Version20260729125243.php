<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729125243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un numéro positif et unique par anime aux saisons.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE season ADD number INT DEFAULT NULL');
        $this->addSql(
            'UPDATE season s
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY anime_id ORDER BY id) AS generated_number
                FROM season
            ) ranked ON ranked.id = s.id
            SET s.number = ranked.generated_number'
        );
        $this->addSql('ALTER TABLE season MODIFY number INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SEASON_ANIME_NUMBER ON season (anime_id, number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_SEASON_ANIME_NUMBER ON season');
        $this->addSql('ALTER TABLE season DROP number');
    }
}
