<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160629122028 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE tried_enters RENAME TO tried_enters_fix');
        $this->addSql('CREATE TABLE tried_enters (user_id INT NOT NULL, promo_key_id INT NOT NULL, created DATETIME NOT NULL, INDEX IDX_27C5663EA76ED395 (user_id), INDEX IDX_27C5663E14914A7E (promo_key_id), PRIMARY KEY(user_id, promo_key_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO tried_enters SELECT user_id, promo_key_id, created FROM tried_enters_fix GROUP BY user_id, promo_key_id');
        $this->addSql('DROP TABLE tried_enters_fix');
        $this->addSql('ALTER TABLE tried_enters ADD CONSTRAINT FK_27C5663EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tried_enters ADD CONSTRAINT FK_27C5663E14914A7E FOREIGN KEY (promo_key_id) REFERENCES promo_key (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE tried_enters RENAME TO tried_enters_fix');
        $this->addSql('CREATE TABLE tried_enters (id INT AUTO_INCREMENT NOT NULL, promo_key_id INT NOT NULL, user_id INT NOT NULL, created DATETIME NOT NULL, INDEX IDX_409219AFA76ED395 (user_id), INDEX IDX_409219AF14914A7E (promo_key_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO tried_enters (user_id, promo_key_id, created) SELECT * FROM tried_enters_fix');
        $this->addSql('DROP TABLE tried_enters_fix');
        $this->addSql('ALTER TABLE tried_enters ADD CONSTRAINT FK_409219AF14914A7E FOREIGN KEY (promo_key_id) REFERENCES promo_key (id)');
        $this->addSql('ALTER TABLE tried_enters ADD CONSTRAINT FK_409219AFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }
}
