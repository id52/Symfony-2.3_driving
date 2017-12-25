<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170214000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `settings` (`_key`, `value`, `type`) VALUES ('after_changing_phone_number_text', 'Номер телефона изменён', 'string');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `settings` WHERE `_key` = 'after_changing_phone_number_text';");
    }
}
