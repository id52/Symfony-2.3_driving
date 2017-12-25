<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160705140907 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('api_add_user_email_title', 'Регистрация нового пользователя', 'string');");
        $this->addSql("INSERT INTO `settings_notifies` (`_key`, `value`, `type`) VALUES ('api_add_user_email_text', 'Ваш пароль для {{ url }} {{ password }}', 'string');");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'api_add_user_email_title';");
        $this->addSql("DELETE FROM `settings_notifies` WHERE `_key` = 'api_add_user_email_text';");
    }
}
