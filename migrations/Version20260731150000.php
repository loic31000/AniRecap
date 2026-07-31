<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Déduplique les favoris racine directs et garantit leur unicité par utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE duplicate FROM favorite duplicate INNER JOIN favorite keeper ON keeper.user_id = duplicate.user_id AND keeper.anime_id = duplicate.anime_id AND keeper.id < duplicate.id WHERE duplicate.anime_id IS NOT NULL');
        $this->addSql('DELETE duplicate FROM favorite duplicate INNER JOIN favorite keeper ON keeper.user_id = duplicate.user_id AND keeper.manga_id = duplicate.manga_id AND keeper.id < duplicate.id WHERE duplicate.manga_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAVORITE_USER_ANIME ON favorite (user_id, anime_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAVORITE_USER_MANGA ON favorite (user_id, manga_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_FAVORITE_USER_ANIME ON favorite');
        $this->addSql('DROP INDEX UNIQ_FAVORITE_USER_MANGA ON favorite');
    }
}
