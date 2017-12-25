<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161227064806 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users ADD moderator_id INT DEFAULT NULL, ADD is_old TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D0AFA354 FOREIGN KEY (moderator_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9D0AFA354 ON users (moderator_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9D0AFA354');
        $this->addSql('DROP INDEX IDX_1483A5E9D0AFA354 ON users');
        $this->addSql('ALTER TABLE users DROP moderator_id, DROP is_old');
    }
}
