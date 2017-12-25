<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170209000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `settings` (`_key`, `value`, `type`) VALUES ('medical_certificate_is_not_issued_and_the_agreement_is_not_signed', '0', 'boolean');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `settings` WHERE `_key` = 'medical_certificate_is_not_issued_and_the_agreement_is_not_signed';");
    }
}
