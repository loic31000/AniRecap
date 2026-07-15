<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715093355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB0347E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_937AB0347E3C61F9 ON `character` (owner_id)');
        $this->addSql('ALTER TABLE diaporama ADD spoiler_level VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE summary ADD spoiler_level VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB0347E3C61F9');
        $this->addSql('DROP INDEX IDX_937AB0347E3C61F9 ON `character`');
        $this->addSql('ALTER TABLE `character` DROP owner_id');
        $this->addSql('ALTER TABLE diaporama DROP spoiler_level');
        $this->addSql('ALTER TABLE summary DROP spoiler_level');
    }
}
