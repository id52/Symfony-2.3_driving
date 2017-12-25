<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160912065508 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_user (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_F7129A803AD8644E (user_source), INDEX IDX_F7129A80233D34C1 (user_target), PRIMARY KEY(user_source, user_target)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A803AD8644E FOREIGN KEY (user_source) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A80233D34C1 FOREIGN KEY (user_target) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E932C70D4E');
        $this->addSql('DROP INDEX UNIQ_1483A5E932C70D4E ON users');
        $this->addSql('ALTER TABLE users DROP final_doc_moderator_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_user');
        $this->addSql('ALTER TABLE users ADD final_doc_moderator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E932C70D4E FOREIGN KEY (final_doc_moderator_id) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E932C70D4E ON users (final_doc_moderator_id)');
    }
}
