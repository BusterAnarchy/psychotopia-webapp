<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213135428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
       $this->addSql('ALTER TABLE topia_molecules ADD has_content_analysis TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
       $this->addSql('ALTER TABLE topia_molecules DROP has_content_analysis');
    }
}
