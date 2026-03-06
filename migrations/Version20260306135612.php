<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306135612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER name TYPE TEXT');
        $this->addSql('ALTER TABLE "user" ALTER surname TYPE TEXT');
        $this->addSql('ALTER TABLE "user" ALTER cristal_id TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER name TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE "user" ALTER surname TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE "user" ALTER cristal_id TYPE VARCHAR(50)');
    }
}
