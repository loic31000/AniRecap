<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730134724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transforme Diaporama en conteneur et conserve chaque ancienne ligne dans une première Slide ordonnée.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        $this->abortIf(
            !$platform instanceof MySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        // La colonne NOT NULL fait échouer la migration si une ancienne ligne ne possède
        // pas exactement une cible. Aucune cible arbitraire ne doit être choisie.
        $this->addSql('CREATE TEMPORARY TABLE migration_diaporama_slide_guard (verified TINYINT NOT NULL)');
        $this->addSql(
            'INSERT INTO migration_diaporama_slide_guard (verified)
             SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE NULL END
             FROM diaporama
             WHERE ((episode_id IS NOT NULL) + (tome_id IS NOT NULL) + (chapitre_id IS NOT NULL)) <> 1'
        );

        $this->addSql('ALTER TABLE diaporama ADD source_type VARCHAR(10) DEFAULT NULL, ADD cover_image_filename VARCHAR(255) DEFAULT NULL');

        $this->addSql(
            'CREATE TABLE slide (
                id INT AUTO_INCREMENT NOT NULL,
                diaporama_id INT NOT NULL,
                episode_id INT DEFAULT NULL,
                tome_id INT DEFAULT NULL,
                chapitre_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT DEFAULT NULL,
                image_filename VARCHAR(255) DEFAULT NULL,
                position INT NOT NULL,
                spoiler_level VARCHAR(20) NOT NULL,
                start_timecode_seconds INT DEFAULT NULL,
                end_timecode_seconds INT DEFAULT NULL,
                INDEX IDX_72EFEE627F32056C (diaporama_id),
                INDEX IDX_72EFEE62362B62A0 (episode_id),
                INDEX IDX_72EFEE6288B33E26 (tome_id),
                INDEX IDX_72EFEE621FBEEF7B (chapitre_id),
                UNIQUE INDEX UNIQ_SLIDE_DIAPORAMA_POSITION (diaporama_id, position),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4'
        );
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE627F32056C FOREIGN KEY (diaporama_id) REFERENCES diaporama (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE62362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE6288B33E26 FOREIGN KEY (tome_id) REFERENCES tome (id)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT FK_72EFEE621FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)');

        $this->addSql(
            "UPDATE diaporama
             SET source_type = CASE
                 WHEN episode_id IS NOT NULL THEN 'anime'
                 WHEN tome_id IS NOT NULL OR chapitre_id IS NOT NULL THEN 'manga'
             END"
        );

        // Les valeurs SQL confirmées de SpoilerLevel sont : aucun, mineur, majeur.
        // Les anciennes valeurs NULL ou inattendues deviennent le niveau neutre "aucun".
        $this->addSql(
            "INSERT INTO slide (
                diaporama_id,
                episode_id,
                tome_id,
                chapitre_id,
                title,
                content,
                image_filename,
                position,
                spoiler_level,
                start_timecode_seconds,
                end_timecode_seconds
            )
            SELECT
                id,
                episode_id,
                tome_id,
                chapitre_id,
                title,
                content,
                NULL,
                1,
                CASE
                    WHEN spoiler_level IN ('aucun', 'mineur', 'majeur') THEN spoiler_level
                    ELSE 'aucun'
                END,
                NULL,
                NULL
            FROM diaporama"
        );

        // Vérification bloquante après copie : même nombre de lignes, une Slide position 1
        // par ancien Diaporama et conservation exacte des trois clés de cible.
        $this->addSql('DELETE FROM migration_diaporama_slide_guard');
        $this->addSql(
            'INSERT INTO migration_diaporama_slide_guard (verified)
             SELECT CASE WHEN
                 (SELECT COUNT(*) FROM diaporama) = (SELECT COUNT(*) FROM slide)
                 AND NOT EXISTS (
                     SELECT 1
                     FROM diaporama d
                     LEFT JOIN slide s ON s.diaporama_id = d.id AND s.position = 1
                     WHERE s.id IS NULL
                        OR NOT (
                            d.episode_id <=> s.episode_id
                            AND d.tome_id <=> s.tome_id
                            AND d.chapitre_id <=> s.chapitre_id
                        )
                 )
             THEN 1 ELSE NULL END'
        );

        $this->addSql('ALTER TABLE diaporama MODIFY source_type VARCHAR(10) NOT NULL');
        $this->addSql("ALTER TABLE diaporama ADD CONSTRAINT CHK_DIAPORAMA_SOURCE_TYPE CHECK (source_type IN ('anime', 'manga'))");
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT CHK_SLIDE_EXACTLY_ONE_TARGET CHECK (((episode_id IS NOT NULL) + (tome_id IS NOT NULL) + (chapitre_id IS NOT NULL)) = 1)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT CHK_SLIDE_POSITION_POSITIVE CHECK (position > 0)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT CHK_SLIDE_START_TIMECODE CHECK (start_timecode_seconds IS NULL OR start_timecode_seconds >= 0)');
        $this->addSql('ALTER TABLE slide ADD CONSTRAINT CHK_SLIDE_END_TIMECODE CHECK (end_timecode_seconds IS NULL OR (start_timecode_seconds IS NOT NULL AND end_timecode_seconds >= start_timecode_seconds))');

        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BE362B62A0');
        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BE88B33E26');
        $this->addSql('ALTER TABLE diaporama DROP FOREIGN KEY FK_776658BE1FBEEF7B');
        $this->addSql('DROP INDEX IDX_776658BE362B62A0 ON diaporama');
        $this->addSql('DROP INDEX IDX_776658BE88B33E26 ON diaporama');
        $this->addSql('DROP INDEX IDX_776658BE1FBEEF7B ON diaporama');
        $this->addSql('ALTER TABLE diaporama DROP episode_id, DROP tome_id, DROP chapitre_id, DROP spoiler_level');
        $this->addSql('DROP TEMPORARY TABLE migration_diaporama_slide_guard');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Un Diaporama peut désormais contenir plusieurs Slides ordonnées. '
            . 'L’ancien modèle ne peut représenter qu’une seule scène et un rollback supprimerait des données.',
        );
    }
}
