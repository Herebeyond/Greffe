<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow partial transplant drafts for coordinator donor assignment flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transplant ALTER transplant_date DROP NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER harvest_side DROP NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER transplant_side DROP NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER total_ischemia_minutes DROP NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER anastomosis_duration DROP NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER peritoneal_position_id DROP NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER immunological_risk_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transplant ALTER transplant_date SET NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER harvest_side SET NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER transplant_side SET NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER total_ischemia_minutes SET NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER anastomosis_duration SET NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER peritoneal_position_id SET NOT NULL');
        $this->addSql('ALTER TABLE transplant ALTER immunological_risk_id SET NOT NULL');
    }
}