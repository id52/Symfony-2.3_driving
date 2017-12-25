<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160531133438 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE tried_enters (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, promo_key_id INT DEFAULT NULL, created DATETIME NOT NULL, INDEX IDX_409219AFA76ED395 (user_id), INDEX IDX_409219AF14914A7E (promo_key_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tried_enters ADD CONSTRAINT FK_409219AFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tried_enters ADD CONSTRAINT FK_409219AF14914A7E FOREIGN KEY (promo_key_id) REFERENCES promo_key (id)');
        $this->addSql('ALTER TABLE users ADD hurry_is_send TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE promo_key DROP tried_enters');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP TABLE tried_enters');
        $this->addSql('ALTER TABLE promo_key ADD tried_enters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE users DROP hurry_is_send');
    }
}
