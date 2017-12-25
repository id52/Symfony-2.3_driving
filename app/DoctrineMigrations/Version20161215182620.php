<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161215182620 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_stat (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, reg_by VARCHAR(255) NOT NULL, reg_type VARCHAR(255) NOT NULL, pay_1_type VARCHAR(255) DEFAULT NULL, discount_1_type VARCHAR(255) DEFAULT NULL, pay_2_type VARCHAR(255) DEFAULT NULL, discount_2_type VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5A39B3E8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_stat ADD CONSTRAINT FK_5A39B3E8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_stat');
    }
}
