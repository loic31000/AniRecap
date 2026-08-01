<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Summary visibility and likes, then backfill missing Episode, Tome and Chapitre summaries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE summary ADD is_public TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE TABLE summary_like (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, summary_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_SUMMARY_LIKE_USER (user_id), INDEX IDX_SUMMARY_LIKE_SUMMARY (summary_id), UNIQUE INDEX UNIQ_SUMMARY_LIKE_USER_SUMMARY (user_id, summary_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE summary_like ADD CONSTRAINT FK_SUMMARY_LIKE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE summary_like ADD CONSTRAINT FK_SUMMARY_LIKE_SUMMARY FOREIGN KEY (summary_id) REFERENCES summary (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO summary (title, content, user_id, episode_id, spoiler_level, is_public)
            SELECT e.title, e.synopsis, e.user_id, e.id, e.spoiler_level, 0 FROM episode e
            WHERE TRIM(COALESCE(e.synopsis, '')) <> ''
            AND NOT EXISTS (SELECT 1 FROM summary s WHERE s.episode_id = e.id)");
        $this->addSql("INSERT INTO summary (title, content, user_id, tome_id, spoiler_level, is_public)
            SELECT t.title, t.synopsis, t.user_id, t.id, t.spoiler_level, 0 FROM tome t
            WHERE TRIM(COALESCE(t.synopsis, '')) <> ''
            AND NOT EXISTS (SELECT 1 FROM summary s WHERE s.tome_id = t.id)");
        $this->addSql("INSERT INTO summary (title, content, user_id, chapitre_id, spoiler_level, is_public)
            SELECT c.title, c.synopsis, c.user_id, c.id, c.spoiler_level, 0 FROM chapitre c
            WHERE TRIM(COALESCE(c.synopsis, '')) <> ''
            AND NOT EXISTS (SELECT 1 FROM summary s WHERE s.chapitre_id = c.id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE summary_like DROP FOREIGN KEY FK_SUMMARY_LIKE_USER');
        $this->addSql('ALTER TABLE summary_like DROP FOREIGN KEY FK_SUMMARY_LIKE_SUMMARY');
        $this->addSql('DROP TABLE summary_like');
        $this->addSql('ALTER TABLE summary DROP is_public');
    }
}
