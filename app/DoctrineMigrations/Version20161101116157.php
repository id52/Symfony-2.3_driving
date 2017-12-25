<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161101116157 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('
        SET @old_number_tickets = (SELECT number_tickets FROM driving_conditions WHERE id=7);
UPDATE driving_conditions SET number_tickets=18 WHERE id=7;

DROP PROCEDURE IF EXISTS add_ticket;
CREATE PROCEDURE add_ticket()
BEGIN
  DECLARE done INT DEFAULT TRUE;
  DECLARE pack_number INT;
  DECLARE t_count INT;
  DECLARE cur_package CURSOR FOR
    SELECT number, COUNT(dt.id) FROM driving_package
      LEFT JOIN driving_ticket AS dt ON dt.p_number=number
    WHERE condition_id=7 GROUP BY number;
  DECLARE EXIT HANDLER FOR NOT FOUND SET done = FALSE;

  OPEN cur_package;
  SET @i = 0;
  WHILE (done = TRUE) DO
    FETCH cur_package INTO pack_number, t_count;
    SET @add_tickets = 18 - t_count;
    SET @counter = 1;
    WHILE (@counter <= @add_tickets) DO
      INSERT INTO driving_ticket (p_number) VALUE (pack_number);
      SET @counter = @counter + 1;
    END WHILE;
    SET @i = @i + 1;
  END WHILE;
  CLOSE cur_package;
END;

call add_ticket();
DROP PROCEDURE IF EXISTS add_ticket;

        ');
        
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
    }
}
