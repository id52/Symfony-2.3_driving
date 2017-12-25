<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160809180309 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('DELETE FROM settings WHERE _key="final_exam_1_max_errors_in_ticket"');
        $this->addSql('INSERT INTO settings SET _key="final_exam_1_extra_time", value=5, type="integer"');
    }

    public function down(Schema $schema)
    {
        $this->addSql('INSERT INTO settings SET _key="final_exam_1_max_errors_in_ticket", value=1, type="integer"');
        $this->addSql('DELETE FROM settings WHERE _key="final_exam_1_extra_time"');
    }
}
