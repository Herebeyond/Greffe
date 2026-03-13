<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313145747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rhesus factor field to donor and patient tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE donor ADD rhesus VARCHAR(1) DEFAULT NULL');
        $this->addSql("UPDATE donor SET rhesus = '+' WHERE rhesus IS NULL");
        $this->addSql('ALTER TABLE donor ALTER COLUMN rhesus SET NOT NULL');
        $this->addSql('ALTER TABLE patient ADD rhesus VARCHAR(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE donor DROP rhesus');
        $this->addSql('ALTER TABLE patient DROP rhesus');
    }
}
