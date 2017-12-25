<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161222160851 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('INSERT INTO `settings` (`_key`, `value`, `type`) VALUES
                        ("unpaid_driving_email_enabled", "0",                                                                "boolean"),
                        ("unpaid_driving_email_text",    "[E-Mail] Текст письма о том, что нужно оплатить вождение {Текст}", "string"),
                        ("unpaid_driving_email_title",   "[E-Mail] Тема письма о том, что нужно оплатить вождение {Текст}",  "string"),
                        ("unpaid_driving_sms_enabled",   "0",                                                                "boolean"),
                        ("unpaid_driving_sms_text",      "[SMS] Текст СМС о том, что нужно оплатить вождение {Текст}",       "string");
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM `settings` WHERE 
                        `_key` = "unpaid_driving_email_enabled" OR
                        `_key` = "unpaid_driving_email_text" OR
                        `_key` = "unpaid_driving_email_title" OR
                        `_key` = "unpaid_driving_sms_enabled" OR
                        `_key` = "unpaid_driving_sms_text";
        ');
    }
}
