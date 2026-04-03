<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert single-file columns to JSON arrays for multi-file support.
 */
final class Version20260327120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert attachment_filename, report_filename, operative_report_filename, protocol_filename to JSON arrays for multi-file support';
    }

    public function up(Schema $schema): void
    {
        // Consultation: attachment_filename (VARCHAR) -> attachment_filenames (JSON)
        $this->addSql('ALTER TABLE consultation ADD COLUMN attachment_filenames JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('UPDATE consultation SET attachment_filenames = json_build_array(attachment_filename) WHERE attachment_filename IS NOT NULL');
        $this->addSql('ALTER TABLE consultation DROP COLUMN attachment_filename');
        $this->addSql('ALTER TABLE consultation ALTER attachment_filenames DROP DEFAULT');

        // BiologicalResult: report_filename (VARCHAR) -> report_filenames (JSON)
        $this->addSql('ALTER TABLE biological_result ADD COLUMN report_filenames JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('UPDATE biological_result SET report_filenames = json_build_array(report_filename) WHERE report_filename IS NOT NULL');
        $this->addSql('ALTER TABLE biological_result DROP COLUMN report_filename');
        $this->addSql('ALTER TABLE biological_result ALTER report_filenames DROP DEFAULT');

        // Transplant: operative_report_filename (VARCHAR) -> operative_report_filenames (JSON)
        $this->addSql('ALTER TABLE transplant ADD COLUMN operative_report_filenames JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('UPDATE transplant SET operative_report_filenames = json_build_array(operative_report_filename) WHERE operative_report_filename IS NOT NULL');
        $this->addSql('ALTER TABLE transplant DROP COLUMN operative_report_filename');
        $this->addSql('ALTER TABLE transplant ALTER operative_report_filenames DROP DEFAULT');

        // Transplant: protocol_filename (VARCHAR) -> protocol_filenames (JSON)
        $this->addSql('ALTER TABLE transplant ADD COLUMN protocol_filenames JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('UPDATE transplant SET protocol_filenames = json_build_array(protocol_filename) WHERE protocol_filename IS NOT NULL');
        $this->addSql('ALTER TABLE transplant DROP COLUMN protocol_filename');
        $this->addSql('ALTER TABLE transplant ALTER protocol_filenames DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // Consultation: revert to single VARCHAR
        $this->addSql('ALTER TABLE consultation ADD COLUMN attachment_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE consultation SET attachment_filename = attachment_filenames->>0 WHERE jsonb_array_length(attachment_filenames) > 0');
        $this->addSql('ALTER TABLE consultation DROP COLUMN attachment_filenames');

        // BiologicalResult: revert to single VARCHAR
        $this->addSql('ALTER TABLE biological_result ADD COLUMN report_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE biological_result SET report_filename = report_filenames->>0 WHERE jsonb_array_length(report_filenames) > 0');
        $this->addSql('ALTER TABLE biological_result DROP COLUMN report_filenames');

        // Transplant: revert operative_report
        $this->addSql('ALTER TABLE transplant ADD COLUMN operative_report_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE transplant SET operative_report_filename = operative_report_filenames->>0 WHERE jsonb_array_length(operative_report_filenames) > 0');
        $this->addSql('ALTER TABLE transplant DROP COLUMN operative_report_filenames');

        // Transplant: revert protocol
        $this->addSql('ALTER TABLE transplant ADD COLUMN protocol_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE transplant SET protocol_filename = protocol_filenames->>0 WHERE jsonb_array_length(protocol_filenames) > 0');
        $this->addSql('ALTER TABLE transplant DROP COLUMN protocol_filenames');
    }
}
