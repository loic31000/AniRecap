<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630111534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anime (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, synopsis LONGTEXT DEFAULT NULL, cover_anime_url VARCHAR(500) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, author VARCHAR(150) DEFAULT NULL, anime_date INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE anime_categorie (anime_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_9223DF30794BBE89 (anime_id), INDEX IDX_9223DF30BCF5E72D (categorie_id), PRIMARY KEY (anime_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE anime_character (anime_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_4824B930794BBE89 (anime_id), INDEX IDX_4824B9301136BE75 (character_id), PRIMARY KEY (anime_id, character_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_497DD6345E237E06 (name), UNIQUE INDEX UNIQ_497DD634989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chapitre (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, synopsis LONGTEXT DEFAULT NULL, cover_chapitre_url VARCHAR(500) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, author VARCHAR(150) DEFAULT NULL, chapitre_date INT DEFAULT NULL, spoiler_level VARCHAR(20) NOT NULL, manga_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8C62B0257B6461 (manga_id), INDEX IDX_8C62B025A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chapitre_categorie (chapitre_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_388958601FBEEF7B (chapitre_id), INDEX IDX_38895860BCF5E72D (categorie_id), PRIMARY KEY (chapitre_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chapitre_character (chapitre_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_E28E3E601FBEEF7B (chapitre_id), INDEX IDX_E28E3E601136BE75 (character_id), PRIMARY KEY (chapitre_id, character_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `character` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, spoiler_level VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE diaporama (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, user_id INT NOT NULL, episode_id INT DEFAULT NULL, tome_id INT DEFAULT NULL, chapitre_id INT DEFAULT NULL, INDEX IDX_776658BEA76ED395 (user_id), INDEX IDX_776658BE362B62A0 (episode_id), INDEX IDX_776658BE88B33E26 (tome_id), INDEX IDX_776658BE1FBEEF7B (chapitre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE diaporama_categorie (diaporama_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_621F68547F32056C (diaporama_id), INDEX IDX_621F6854BCF5E72D (categorie_id), PRIMARY KEY (diaporama_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE episode (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, synopsis LONGTEXT DEFAULT NULL, cover_episode_url VARCHAR(500) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, author VARCHAR(150) DEFAULT NULL, episode_date INT DEFAULT NULL, spoiler_level VARCHAR(20) NOT NULL, season_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_DDAA1CDA4EC001D1 (season_id), INDEX IDX_DDAA1CDAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE episode_categorie (episode_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_F7BF400D362B62A0 (episode_id), INDEX IDX_F7BF400DBCF5E72D (categorie_id), PRIMARY KEY (episode_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE episode_character (episode_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_2DB8260D362B62A0 (episode_id), INDEX IDX_2DB8260D1136BE75 (character_id), PRIMARY KEY (episode_id, character_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favorite (id INT AUTO_INCREMENT NOT NULL, created_at DATE NOT NULL, user_id INT NOT NULL, anime_id INT DEFAULT NULL, season_id INT DEFAULT NULL, episode_id INT DEFAULT NULL, manga_id INT DEFAULT NULL, tome_id INT DEFAULT NULL, chapitre_id INT DEFAULT NULL, INDEX IDX_68C58ED9A76ED395 (user_id), INDEX IDX_68C58ED9794BBE89 (anime_id), INDEX IDX_68C58ED94EC001D1 (season_id), INDEX IDX_68C58ED9362B62A0 (episode_id), INDEX IDX_68C58ED97B6461 (manga_id), INDEX IDX_68C58ED988B33E26 (tome_id), INDEX IDX_68C58ED91FBEEF7B (chapitre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE manga (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, synopsis LONGTEXT DEFAULT NULL, cover_manga_url VARCHAR(500) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, manga_date INT DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, author VARCHAR(150) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE manga_categorie (manga_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_A6DF5F997B6461 (manga_id), INDEX IDX_A6DF5F99BCF5E72D (categorie_id), PRIMARY KEY (manga_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE manga_character (manga_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_7CD839997B6461 (manga_id), INDEX IDX_7CD839991136BE75 (character_id), PRIMARY KEY (manga_id, character_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recommandation (id INT AUTO_INCREMENT NOT NULL, last_updated DATE NOT NULL, popularity_score INT NOT NULL, user_id INT NOT NULL, anime_id INT DEFAULT NULL, manga_id INT DEFAULT NULL, INDEX IDX_C7782A28A76ED395 (user_id), INDEX IDX_C7782A28794BBE89 (anime_id), INDEX IDX_C7782A287B6461 (manga_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, synopsis LONGTEXT DEFAULT NULL, cover_season_url VARCHAR(500) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, author VARCHAR(150) DEFAULT NULL, season_date INT DEFAULT NULL, anime_id INT NOT NULL, INDEX IDX_F0E45BA9794BBE89 (anime_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE season_categorie (season_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_C24F98DB4EC001D1 (season_id), INDEX IDX_C24F98DBBCF5E72D (categorie_id), PRIMARY KEY (season_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE season_character (season_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_1848FEDB4EC001D1 (season_id), INDEX IDX_1848FEDB1136BE75 (character_id), PRIMARY KEY (season_id, character_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE spoiler_preference (id INT AUTO_INCREMENT NOT NULL, hide_spoiler TINYINT DEFAULT 1 NOT NULL, spoiler_level VARCHAR(20) NOT NULL, user_id INT NOT NULL, episode_id INT DEFAULT NULL, tome_id INT DEFAULT NULL, chapitre_id INT DEFAULT NULL, character_id INT DEFAULT NULL, INDEX IDX_8B8669C0A76ED395 (user_id), INDEX IDX_8B8669C0362B62A0 (episode_id), INDEX IDX_8B8669C088B33E26 (tome_id), INDEX IDX_8B8669C01FBEEF7B (chapitre_id), INDEX IDX_8B8669C01136BE75 (character_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE summary (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, user_id INT NOT NULL, anime_id INT DEFAULT NULL, season_id INT DEFAULT NULL, episode_id INT DEFAULT NULL, manga_id INT DEFAULT NULL, tome_id INT DEFAULT NULL, chapitre_id INT DEFAULT NULL, INDEX IDX_CE286663A76ED395 (user_id), INDEX IDX_CE286663794BBE89 (anime_id), INDEX IDX_CE2866634EC001D1 (season_id), INDEX IDX_CE286663362B62A0 (episode_id), INDEX IDX_CE2866637B6461 (manga_id), INDEX IDX_CE28666388B33E26 (tome_id), INDEX IDX_CE2866631FBEEF7B (chapitre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tome (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, synopsis LONGTEXT DEFAULT NULL, cover_tome_url VARCHAR(500) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, author VARCHAR(150) DEFAULT NULL, tome_date INT DEFAULT NULL, spoiler_level VARCHAR(20) NOT NULL, manga_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6B19E4F77B6461 (manga_id), INDEX IDX_6B19E4F7A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tome_categorie (tome_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_7BEE023188B33E26 (tome_id), INDEX IDX_7BEE0231BCF5E72D (categorie_id), PRIMARY KEY (tome_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tome_character (tome_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_A1E9643188B33E26 (tome_id), INDEX IDX_A1E964311136BE75 (character_id), PRIMARY KEY (tome_id, character_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password_hash VARCHAR(255) NOT NULL, username VARCHAR(50) NOT NULL, avatar_url VARCHAR(500) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64986CC499D (username), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vote (id INT AUTO_INCREMENT NOT NULL, `like` TINYINT NOT NULL, user_id INT NOT NULL, anime_id INT DEFAULT NULL, manga_id INT DEFAULT NULL, INDEX IDX_5A108564A76ED395 (user_id), INDEX IDX_5A108564794BBE89 (anime_id), INDEX IDX_5A1085647B6461 (manga_id), UNIQUE INDEX uq_vote_user_anime (user_id, anime_id), UNIQUE INDEX uq_vote_user_manga (user_id, manga_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE anime_categorie ADD CONSTRAINT FK_9223DF30794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE anime_categorie ADD CONSTRAINT FK_9223DF30BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE anime_character ADD CONSTRAINT FK_4824B930794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE anime_character ADD CONSTRAINT FK_4824B9301136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapitre ADD CONSTRAINT FK_8C62B0257B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapitre ADD CONSTRAINT FK_8C62B025A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chapitre_categorie ADD CONSTRAINT FK_388958601FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapitre_categorie ADD CONSTRAINT FK_38895860BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapitre_character ADD CONSTRAINT FK_E28E3E601FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapitre_character ADD CONSTRAINT FK_E28E3E601136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE diaporama ADD CONSTRAINT FK_776658BEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE diaporama ADD CONSTRAINT FK_776658BE362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE diaporama ADD CONSTRAINT FK_776658BE88B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id)');
        $this->addSql('ALTER TABLE diaporama ADD CONSTRAINT FK_776658BE1FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)');
        $this->addSql('ALTER TABLE diaporama_categorie ADD CONSTRAINT FK_621F68547F32056C FOREIGN KEY (diaporama_id) REFERENCES diaporama (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE diaporama_categorie ADD CONSTRAINT FK_621F6854BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode ADD CONSTRAINT FK_DDAA1CDA4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode ADD CONSTRAINT FK_DDAA1CDAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE episode_categorie ADD CONSTRAINT FK_F7BF400D362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode_categorie ADD CONSTRAINT FK_F7BF400DBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode_character ADD CONSTRAINT FK_2DB8260D362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode_character ADD CONSTRAINT FK_2DB8260D1136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED94EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED97B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED988B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED91FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)');
        $this->addSql('ALTER TABLE manga_categorie ADD CONSTRAINT FK_A6DF5F997B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_categorie ADD CONSTRAINT FK_A6DF5F99BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_character ADD CONSTRAINT FK_7CD839997B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_character ADD CONSTRAINT FK_7CD839991136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A28A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A28794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id)');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A287B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT FK_F0E45BA9794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season_categorie ADD CONSTRAINT FK_C24F98DB4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season_categorie ADD CONSTRAINT FK_C24F98DBBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season_character ADD CONSTRAINT FK_1848FEDB4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season_character ADD CONSTRAINT FK_1848FEDB1136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C088B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C01FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)');
        $this->addSql('ALTER TABLE spoiler_preference ADD CONSTRAINT FK_8B8669C01136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE286663A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE286663794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE2866634EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE286663362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE2866637B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE28666388B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id)');
        $this->addSql('ALTER TABLE summary ADD CONSTRAINT FK_CE2866631FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)');
        $this->addSql('ALTER TABLE tome ADD CONSTRAINT FK_6B19E4F77B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tome ADD CONSTRAINT FK_6B19E4F7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tome_categorie ADD CONSTRAINT FK_7BEE023188B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tome_categorie ADD CONSTRAINT FK_7BEE0231BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tome_character ADD CONSTRAINT FK_A1E9643188B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tome_character ADD CONSTRAINT FK_A1E964311136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085647B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anime_categorie DROP FOREIGN KEY FK_9223DF30794BBE89');
        $this->addSql('ALTER TABLE anime_categorie DROP FOREIGN KEY FK_9223DF30BCF5E72D');
        $this->addSql('ALTER TABLE anime_character DROP FOREIGN KEY FK_4824B930794BBE89');
        $this->addSql('ALTER TABLE anime_character DROP FOREIGN KEY FK_4824B9301136BE75');
        $this->addSql('ALTER TABLE chapitre DROP FOREIGN KEY FK_8C62B0257B6461');
        $this->addSql('ALTER TABLE chapitre DROP FOREIGN KEY FK_8C62B025A76ED395');
        $this->addSql('ALTER TABLE chapitre_categorie DROP FOREIGN KEY FK_388958601FBEEF7B');
        $this->addSql('ALTER TABLE chapitre_categorie DROP FOREIGN KEY FK_38895860BCF5E72D');
        $this->addSql('ALTER TABLE chapitre_character DROP FOREIGN KEY FK_E28E3E601FBEEF7B');
        $this->addSql('ALTER TABLE chapitre_character DROP FOREIGN KEY FK_E28E3E601136BE75');
        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BEA76ED395');
        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BE362B62A0');
        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BE88B33E26');
        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BE1FBEEF7B');
        $this->addSql('ALTER TABLE diaporama_categorie DROP FOREIGN KEY FK_621F68547F32056C');
        $this->addSql('ALTER TABLE diaporama_categorie DROP FOREIGN KEY FK_621F6854BCF5E72D');
        $this->addSql('ALTER TABLE episode DROP FOREIGN KEY FK_DDAA1CDA4EC001D1');
        $this->addSql('ALTER TABLE episode DROP FOREIGN KEY FK_DDAA1CDAA76ED395');
        $this->addSql('ALTER TABLE episode_categorie DROP FOREIGN KEY FK_F7BF400D362B62A0');
        $this->addSql('ALTER TABLE episode_categorie DROP FOREIGN KEY FK_F7BF400DBCF5E72D');
        $this->addSql('ALTER TABLE episode_character DROP FOREIGN KEY FK_2DB8260D362B62A0');
        $this->addSql('ALTER TABLE episode_character DROP FOREIGN KEY FK_2DB8260D1136BE75');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9794BBE89');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED94EC001D1');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9362B62A0');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED97B6461');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED988B33E26');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED91FBEEF7B');
        $this->addSql('ALTER TABLE manga_categorie DROP FOREIGN KEY FK_A6DF5F997B6461');
        $this->addSql('ALTER TABLE manga_categorie DROP FOREIGN KEY FK_A6DF5F99BCF5E72D');
        $this->addSql('ALTER TABLE manga_character DROP FOREIGN KEY FK_7CD839997B6461');
        $this->addSql('ALTER TABLE manga_character DROP FOREIGN KEY FK_7CD839991136BE75');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A28A76ED395');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A28794BBE89');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A287B6461');
        $this->addSql('ALTER TABLE season DROP FOREIGN KEY FK_F0E45BA9794BBE89');
        $this->addSql('ALTER TABLE season_categorie DROP FOREIGN KEY FK_C24F98DB4EC001D1');
        $this->addSql('ALTER TABLE season_categorie DROP FOREIGN KEY FK_C24F98DBBCF5E72D');
        $this->addSql('ALTER TABLE season_character DROP FOREIGN KEY FK_1848FEDB4EC001D1');
        $this->addSql('ALTER TABLE season_character DROP FOREIGN KEY FK_1848FEDB1136BE75');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C0A76ED395');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C0362B62A0');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C088B33E26');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C01FBEEF7B');
        $this->addSql('ALTER TABLE spoiler_preference DROP FOREIGN KEY FK_8B8669C01136BE75');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE286663A76ED395');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE286663794BBE89');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE2866634EC001D1');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE286663362B62A0');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE2866637B6461');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE28666388B33E26');
        $this->addSql('ALTER TABLE summary DROP FOREIGN KEY FK_CE2866631FBEEF7B');
        $this->addSql('ALTER TABLE tome DROP FOREIGN KEY FK_6B19E4F77B6461');
        $this->addSql('ALTER TABLE tome DROP FOREIGN KEY FK_6B19E4F7A76ED395');
        $this->addSql('ALTER TABLE tome_categorie DROP FOREIGN KEY FK_7BEE023188B33E26');
        $this->addSql('ALTER TABLE tome_categorie DROP FOREIGN KEY FK_7BEE0231BCF5E72D');
        $this->addSql('ALTER TABLE tome_character DROP FOREIGN KEY FK_A1E9643188B33E26');
        $this->addSql('ALTER TABLE tome_character DROP FOREIGN KEY FK_A1E964311136BE75');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564A76ED395');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564794BBE89');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A1085647B6461');
        $this->addSql('DROP TABLE anime');
        $this->addSql('DROP TABLE anime_categorie');
        $this->addSql('DROP TABLE anime_character');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE chapitre');
        $this->addSql('DROP TABLE chapitre_categorie');
        $this->addSql('DROP TABLE chapitre_character');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE diaporama');
        $this->addSql('DROP TABLE diaporama_categorie');
        $this->addSql('DROP TABLE episode');
        $this->addSql('DROP TABLE episode_categorie');
        $this->addSql('DROP TABLE episode_character');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE manga');
        $this->addSql('DROP TABLE manga_categorie');
        $this->addSql('DROP TABLE manga_character');
        $this->addSql('DROP TABLE recommandation');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE season_categorie');
        $this->addSql('DROP TABLE season_character');
        $this->addSql('DROP TABLE spoiler_preference');
        $this->addSql('DROP TABLE summary');
        $this->addSql('DROP TABLE tome');
        $this->addSql('DROP TABLE tome_categorie');
        $this->addSql('DROP TABLE tome_character');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE vote');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
