<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160824190015 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users ADD final_doc_moderator_id INT DEFAULT NULL, ADD final_doc_get_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E932C70D4E FOREIGN KEY (final_doc_moderator_id) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E932C70D4E ON users (final_doc_moderator_id)');
        $this->addSql('ALTER TABLE driving_package ADD moderator_id INT DEFAULT NULL, ADD received_at DATETIME DEFAULT NULL, CHANGE received status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE driving_package ADD CONSTRAINT FK_453689BFD0AFA354 FOREIGN KEY (moderator_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_453689BFD0AFA354 ON driving_package (moderator_id)');
        $this->addSql('ALTER TABLE payments_logs DROP FOREIGN KEY FK_8A8D45B82E4A5571');
        $this->addSql('DROP INDEX IDX_8A8D45B82E4A5571 ON payments_logs');
        $this->addSql('ALTER TABLE payments_logs DROP drive_condition_id');
        $this->addSql('ALTER TABLE payments_logs ADD p_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD CONSTRAINT FK_8A8D45B83BBD6950 FOREIGN KEY (p_number) REFERENCES driving_package (number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8A8D45B83BBD6950 ON payments_logs (p_number)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE driving_package DROP FOREIGN KEY FK_453689BFD0AFA354');
        $this->addSql('DROP INDEX IDX_453689BFD0AFA354 ON driving_package');
        $this->addSql('ALTER TABLE driving_package DROP moderator_id, DROP received_at, CHANGE status received VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs DROP FOREIGN KEY FK_8A8D45B83BBD6950');
        $this->addSql('DROP INDEX UNIQ_8A8D45B83BBD6950 ON payments_logs');
        $this->addSql('ALTER TABLE payments_logs DROP p_number');
        $this->addSql('ALTER TABLE payments_logs ADD drive_condition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD CONSTRAINT FK_8A8D45B82E4A5571 FOREIGN KEY (drive_condition_id) REFERENCES driving_conditions (id)');
        $this->addSql('CREATE INDEX IDX_8A8D45B82E4A5571 ON payments_logs (drive_condition_id)');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E932C70D4E');
        $this->addSql('DROP INDEX UNIQ_1483A5E932C70D4E ON users');
        $this->addSql('ALTER TABLE users DROP final_doc_moderator_id, DROP final_doc_get_at');
    }
}
