<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161215091034 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users ADD api_med_form TINYINT(1) DEFAULT \'0\' NOT NULL, ADD api_contract_sign TINYINT(1) DEFAULT \'0\' NOT NULL, ADD api_med_con_notify_date DATE DEFAULT NULL');

        $this->addSql('INSERT INTO `settings` (`_key`, `value`, `type`) VALUES
                        ("api_contract_sign", "[Popup] Вы не подписали договор {Текст}",                           "string"),
                        ("api_med_form",      "[Popup] Вы не оформили мед справку {Текст}",                        "string"),
                        ("api_med_con",       "[Popup] Вы не оформили мед справку и не подписали договор {Текст}", "string");
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users DROP api_med_form, DROP api_contract_sign, DROP api_med_con_notify_date');

        $this->addSql('DELETE FROM `settings` WHERE 
                        `_key` = "api_contract_sign" OR
                        `_key` = "api_med_form" OR
                        `_key` = "api_med_con";
        ');
    }
}
