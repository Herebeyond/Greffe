<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add authentication tracking fields to User and create LoginActivity table.
 */
final class Version20260306160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authentication tracking: User fields (isActive, lastLoginAt, createdAt) and LoginActivity table';
    }

    public function up(Schema $schema): void
    {
        // Add new fields to user table
        $this->addSql('ALTER TABLE "user" ADD is_active BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('COMMENT ON COLUMN "user".last_login_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');

        // Create login_activity table
        $this->addSql('CREATE TABLE login_activity (
            id SERIAL PRIMARY KEY,
            user_id INT DEFAULT NULL,
            user_identifier VARCHAR(255) DEFAULT NULL,
            activity_type VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            session_id VARCHAR(128) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            CONSTRAINT fk_login_activity_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL
        )');
        $this->addSql('COMMENT ON COLUMN login_activity.created_at IS \'(DC2Type:datetime_immutable)\'');
        
        // Create indexes for performance
        $this->addSql('CREATE INDEX idx_login_activity_user ON login_activity (user_id)');
        $this->addSql('CREATE INDEX idx_login_activity_type ON login_activity (activity_type)');
        $this->addSql('CREATE INDEX idx_login_activity_created ON login_activity (created_at)');
    }

    public function down(Schema $schema): void
    {
        // Drop login_activity table
        $this->addSql('DROP TABLE login_activity');

        // Remove fields from user table
        $this->addSql('ALTER TABLE "user" DROP COLUMN is_active');
        $this->addSql('ALTER TABLE "user" DROP COLUMN last_login_at');
        $this->addSql('ALTER TABLE "user" DROP COLUMN created_at');
    }
}
