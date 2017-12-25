<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150509124334 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE questions ADD is_pdd TINYINT(1) NOT NULL');
        $this->addSql('UPDATE questions AS q LEFT JOIN themes AS t ON t.id=q.theme_id LEFT JOIN subjects AS s ON s.id=t.subject_id SET q.is_pdd=s.is_pdd');
        $this->addSql('ALTER TABLE subjects DROP is_pdd');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE questions DROP is_pdd');
        $this->addSql('ALTER TABLE subjects ADD is_pdd TINYINT(1) NOT NULL');
    }
}
