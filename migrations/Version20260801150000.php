<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused legacy models, consolidate work release dates and enforce one target per favorite and summary.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM recommandation') > 0,
            'The recommandation table is not empty; migrate its data before removing it.',
        );
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM spoiler_preference') > 0,
            'The spoiler_preference table is not empty; migrate its data before removing it.',
        );
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM vote') > 0,
            'The legacy vote table is not empty; migrate its data before removing it.',
        );
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages') > 0,
            'The messenger_messages table is not empty; process its messages before removing it.',
        );
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM summary WHERE ((anime_id IS NOT NULL) + (season_id IS NOT NULL) + (episode_id IS NOT NULL) + (manga_id IS NOT NULL) + (tome_id IS NOT NULL) + (chapitre_id IS NOT NULL)) <> 1') > 0,
            'Every summary must have exactly one target before adding the database constraint.',
        );
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM favorite WHERE ((anime_id IS NOT NULL) + (season_id IS NOT NULL) + (episode_id IS NOT NULL) + (manga_id IS NOT NULL) + (tome_id IS NOT NULL) + (chapitre_id IS NOT NULL)) <> 1') > 0,
            'Every favorite must have exactly one target before adding the database constraint.',
        );

        $this->addSql("UPDATE anime SET release_date = STR_TO_DATE(CONCAT(anime_date, '-01-01'), '%Y-%m-%d') WHERE release_date IS NULL AND anime_date IS NOT NULL");
        $this->addSql("UPDATE manga SET release_date = STR_TO_DATE(CONCAT(manga_date, '-01-01'), '%Y-%m-%d') WHERE release_date IS NULL AND manga_date IS NOT NULL");
        $this->addSql('ALTER TABLE anime DROP anime_date');
        $this->addSql('ALTER TABLE manga DROP manga_date');

        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT CHK_FAVORITE_EXACTLY_ONE_TARGET CHECK (((anime_id IS NOT NULL) + (season_id IS NOT NULL) + (episode_id IS NOT NULL) + (manga_id IS NOT NULL) + (tome_id IS NOT NULL) + (chapitre_id IS NOT NULL)) = 1)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT CHK_SUMMARY_EXACTLY_ONE_TARGET CHECK (((anime_id IS NOT NULL) + (season_id IS NOT NULL) + (episode_id IS NOT NULL) + (manga_id IS NOT NULL) + (tome_id IS NOT NULL) + (chapitre_id IS NOT NULL)) = 1)');

        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A28794BBE89');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A287B6461');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A28A76ED395');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C01136BE75');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C01FBEEF7B');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C0362B62A0');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C088B33E26');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C0A76ED395');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564794BBE89');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A1085647B6461');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564A76ED395');
        $this->addSql('DROP TABLE recommandation');
        $this->addSql('DROP TABLE spoiler_preference');
        $this->addSql('DROP TABLE vote');
        $this->addSql('DROP TABLE messenger_messages');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE favorite DROP CHECK CHK_FAVORITE_EXACTLY_ONE_TARGET');
        $this->addSql('ALTER TABLE summary DROP CHECK CHK_SUMMARY_EXACTLY_ONE_TARGET');
        $this->addSql('ALTER TABLE anime ADD anime_date INT DEFAULT NULL');
        $this->addSql('ALTER TABLE manga ADD manga_date INT DEFAULT NULL');
        $this->addSql('UPDATE anime SET anime_date = YEAR(release_date) WHERE release_date IS NOT NULL');
        $this->addSql('UPDATE manga SET manga_date = YEAR(release_date) WHERE release_date IS NOT NULL');

        $this->addSql("CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE recommandation (id INT AUTO_INCREMENT NOT NULL, last_updated DATE NOT NULL, popularity_score INT NOT NULL, user_id INT NOT NULL, anime_id INT DEFAULT NULL, manga_id INT DEFAULT NULL, INDEX IDX_C7782A28A76ED395 (user_id), INDEX IDX_C7782A28794BBE89 (anime_id), INDEX IDX_C7782A287B6461 (manga_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE spoiler_preference (id INT AUTO_INCREMENT NOT NULL, spoiler_level VARCHAR(20) NOT NULL, user_id INT NOT NULL, episode_id INT DEFAULT NULL, tome_id INT DEFAULT NULL, chapitre_id INT DEFAULT NULL, character_id INT DEFAULT NULL, hide_spoiler TINYINT DEFAULT 1 NOT NULL, INDEX IDX_8B8669C0A76ED395 (user_id), INDEX IDX_8B8669C0362B62A0 (episode_id), INDEX IDX_8B8669C088B33E26 (tome_id), INDEX IDX_8B8669C01FBEEF7B (chapitre_id), INDEX IDX_8B8669C01136BE75 (character_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE vote (id INT AUTO_INCREMENT NOT NULL, `like` TINYINT NOT NULL, user_id INT NOT NULL, anime_id INT DEFAULT NULL, manga_id INT DEFAULT NULL, UNIQUE INDEX uq_vote_user_anime (user_id, anime_id), UNIQUE INDEX uq_vote_user_manga (user_id, manga_id), INDEX IDX_5A108564A76ED395 (user_id), INDEX IDX_5A108564794BBE89 (anime_id), INDEX IDX_5A1085647B6461 (manga_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A28794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id)');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A287B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A28A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C01136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C01FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C088B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085647B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
