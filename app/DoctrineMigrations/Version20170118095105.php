<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170118095105 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('INSERT INTO `settings` (`_key`, `value`, `type`) VALUES
                        ("attempts_reset_progress_title", "Заголовок уведомления пользователю о том, что его прогресс обучения сброшен", "string");
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `settings` WHERE 
                        `_key` = "attempts_reset_progress_title";
        ');
    }
}
