<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the publication date used by the visitor Home page.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE summary ADD published_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE summary SET published_at = CURRENT_TIMESTAMP WHERE is_public = 1 AND (episode_id IS NOT NULL OR tome_id IS NOT NULL OR chapitre_id IS NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE summary DROP published_at');
    }
}
