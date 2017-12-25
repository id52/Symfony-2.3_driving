<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170111090837 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('INSERT INTO `settings` (`_key`, `value`, `type`) VALUES
                        ("confirmation_code_time_to_resend_text", "Повторная отправка кода возможна через {{ timer_resending }}", "string");
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `settings` WHERE 
                        `_key` = "confirmation_code_time_to_resend_text";
        ');
    }
}
