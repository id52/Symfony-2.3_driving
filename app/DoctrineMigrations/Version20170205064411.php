<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170205064411 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `second_payment_posts` (`user_id`, `log_id`, `created_at`, `sended_at`)
                          SELECT
                            pl.uid,
                            pl.id,
                            pl.updated_at,
                            pl.updated_at
                          FROM payments_logs AS pl
                            LEFT JOIN users AS u ON u.id = pl.uid
                          WHERE pl.comment LIKE CONCAT(\'%"services":"\', (SELECT GROUP_CONCAT(s.id)
                                                                            FROM services AS s
                                                                            WHERE s.type = \'training\'), \'"%\')
                                AND pl.s_type != \'api\'
                                AND u.by_api = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
