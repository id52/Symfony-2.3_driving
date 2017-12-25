<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160629163118 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('DELETE FROM payments_logs WHERE uid IN (SELECT uid FROM (SELECT uid FROM payments_logs GROUP BY uid HAVING SUM(paid)>0) AS tmp) AND paid=0');
        $this->addSql('DELETE FROM tried_enters WHERE user_id IN (SELECT uid FROM payments_logs)');
    }

    public function down(Schema $schema)
    {
    }
}
