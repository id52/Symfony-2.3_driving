<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161117202006 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE owe_stages (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, number_stage INT NOT NULL, sum INT NOT NULL, paid TINYINT(1) NOT NULL, start DATETIME NOT NULL, end DATETIME NOT NULL, INDEX IDX_845265D1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE owe_stages ADD CONSTRAINT FK_845265D1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD driving_paid_at DATETIME DEFAULT NULL, ADD owe_stage_end DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD owe_stage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD CONSTRAINT FK_8A8D45B82B953D0F FOREIGN KEY (owe_stage_id) REFERENCES owe_stages (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8A8D45B82B953D0F ON payments_logs (owe_stage_id)');
        $this->addSql('UPDATE users LEFT JOIN (SELECT u.id, dp.sale_at FROM users u LEFT JOIN driving_package dp ON (dp.user_id = u.id) WHERE dp.user_id IS NOT NULL AND dp.sale_at IS NOT NULL) tmp ON tmp.id=users.id SET users.driving_paid_at=tmp.sale_at');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payments_logs DROP FOREIGN KEY FK_8A8D45B82B953D0F');
        $this->addSql('DROP TABLE owe_stages');
        $this->addSql('DROP INDEX UNIQ_8A8D45B82B953D0F ON payments_logs');
        $this->addSql('ALTER TABLE payments_logs DROP owe_stage_id');
        $this->addSql('ALTER TABLE users DROP driving_paid_at, DROP owe_stage_end');
    }
}
