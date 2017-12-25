<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160630125008 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE promo_key DROP FOREIGN KEY FK_CC96ECCDD0C07AFF');
        $this->addSql('ALTER TABLE promo_key ADD CONSTRAINT FK_CC96ECCDD0C07AFF FOREIGN KEY (promo_id) REFERENCES promo (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE promo_key DROP FOREIGN KEY FK_CC96ECCDD0C07AFF');
        $this->addSql('ALTER TABLE promo_key ADD CONSTRAINT FK_CC96ECCDD0C07AFF FOREIGN KEY (promo_id) REFERENCES promo (id)');
    }
}
