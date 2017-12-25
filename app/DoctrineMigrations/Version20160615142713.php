<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160615142713 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE payments_logs MODIFY `s_type` enum(\'robokassa\', \'psb\', \'api\') COLLATE utf8_unicode_ci COMMENT \'(DC2Type:enumpayment)\'');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE payments_logs MODIFY `s_type` enum(\'robokassa\', \'psb\') COLLATE utf8_unicode_ci COMMENT \'(DC2Type:enumpayment)\'');
    }
}
