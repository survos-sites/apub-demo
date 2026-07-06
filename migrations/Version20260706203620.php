<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706203620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post ADD about_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD about_label VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        //
        // The generated diff also included several "CREATE SCHEMA" statements for
        // TimescaleDB's own internal schemas (_timescaledb_internal, etc.) — spurious
        // leakage from the local dev Postgres having the extension installed.
        // schema_filter excludes table names, not whole schema namespaces, so
        // migrations:diff still sees them as "current reality" and tries to encode
        // recreating them on down(). Stripped: our app must never manage those.
        $this->addSql('ALTER TABLE post DROP about_url');
        $this->addSql('ALTER TABLE post DROP about_label');
    }
}
