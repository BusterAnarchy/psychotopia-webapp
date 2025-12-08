<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208153340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE topia_molecules (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, definition VARCHAR(255) DEFAULT NULL, wiki_url VARCHAR(255) DEFAULT NULL, ratio_base_sel DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rcache RENAME TO topia_r_cache');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE topia_molecules');
        $this->addSql('ALTER TABLE topia_r_cache RENAME TO rcache ');
    }
}
