<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730075259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un numéro obligatoire et unique par manga aux tomes et chapitres.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chapitre ADD number INT DEFAULT NULL');
        $this->addSql(
            'UPDATE chapitre c
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY manga_id ORDER BY id) AS generated_number
                FROM chapitre
            ) ranked ON ranked.id = c.id
            SET c.number = ranked.generated_number'
        );
        $this->addSql('ALTER TABLE chapitre MODIFY number INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CHAPITRE_MANGA_NUMBER ON chapitre (manga_id, number)');
        $this->addSql('ALTER TABLE tome ADD number INT DEFAULT NULL');
        $this->addSql(
            'UPDATE tome t
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY manga_id ORDER BY id) AS generated_number
                FROM tome
            ) ranked ON ranked.id = t.id
            SET t.number = ranked.generated_number'
        );
        $this->addSql('ALTER TABLE tome MODIFY number INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TOME_MANGA_NUMBER ON tome (manga_id, number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CHAPITRE_MANGA_NUMBER ON chapitre');
        $this->addSql('DROP INDEX UNIQ_TOME_MANGA_NUMBER ON tome');
        $this->addSql('ALTER TABLE chapitre DROP number');
        $this->addSql('ALTER TABLE tome DROP number');
    }
}
