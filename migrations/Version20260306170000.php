<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the patient table for kidney transplant recipients.
 */
final class Version20260306170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create patient table for kidney transplant recipients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE patient (
            id SERIAL PRIMARY KEY,
            file_number VARCHAR(50) NOT NULL,
            last_name TEXT NOT NULL,
            first_name TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            birth_date DATE DEFAULT NULL,
            blood_group VARCHAR(3) DEFAULT NULL,
            sex VARCHAR(1) DEFAULT NULL,
            phone TEXT DEFAULT NULL,
            email TEXT DEFAULT NULL,
            comment TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');

        $this->addSql('COMMENT ON COLUMN patient.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN patient.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Create unique constraint on file_number
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PATIENT_FILE_NUMBER ON patient (file_number)');

        // Create indexes for search performance
        $this->addSql('CREATE INDEX idx_patient_file_number ON patient (file_number)');
        $this->addSql('CREATE INDEX idx_patient_city ON patient (city)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE patient');
    }
}
