<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160715145118 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `settings` (`_key`, `value`, `type`) VALUES ('attempts_days_of_retake', '10', 'integer');");
        $this->addSql("INSERT INTO `settings` (`_key`, `value`, `type`) VALUES ('attempts_to_reset', '30', 'integer');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('info_attempts_title', '[Text] Уведомление о первой попытке сдачи экзамена {Заголовок}', 'string');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('info_attempts_text', '[Text] Уведомление о первой попытке сдачи экзамена {Текст}', 'string');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('attempts_buy_title', '[Text] Уведомление о повторной попытке сдачи экзамена и отсутствии купленных попыток {Заголовок}', 'string');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('attempts_buy_text', '[Text] Уведомление о повторной попытке сдачи экзамена и отсутствии купленных попыток {Текст}', 'string');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('second_attempt_title', '[Text] Уведомление о повторной попытке сдачи экзамена и предложение воспользоваться купленными попытками {Заголовок}', 'string');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('second_attempt_text', '[Text] Уведомление о повторной попытке сдачи экзамена и предложение воспользоваться купленными попытками {Текст}', 'string');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `settings` WHERE `_key` = 'attempts_days_of_retake';");
        $this->addSql("DELETE FROM `settings` WHERE `_key` = 'attempts_to_reset';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'info_attempts_title';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'info_attempts_text';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'attempts_buy_title';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'attempts_buy_text';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'second_attempt_title';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'second_attempt_text';");
    }
}
