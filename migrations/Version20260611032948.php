<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611032948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task table (reference Task slice)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (id UUID NOT NULL, title VARCHAR(255) NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task');
    }
}
