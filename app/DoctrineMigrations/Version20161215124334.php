<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161215124334 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payments_logs ADD admin_id INT DEFAULT NULL, ADD transferred_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD CONSTRAINT FK_8A8D45B8642B8210 FOREIGN KEY (admin_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_8A8D45B8642B8210 ON payments_logs (admin_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payments_logs DROP FOREIGN KEY FK_8A8D45B8642B8210');
        $this->addSql('DROP INDEX IDX_8A8D45B8642B8210 ON payments_logs');
        $this->addSql('ALTER TABLE payments_logs DROP admin_id, DROP transferred_at');
    }
}
