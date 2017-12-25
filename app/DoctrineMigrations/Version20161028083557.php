<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161028083557 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE exam_attempt_logs (id INT AUTO_INCREMENT NOT NULL, final_exam_log_id INT DEFAULT NULL, exam_log_id INT DEFAULT NULL, user_id INT DEFAULT NULL, subject_id INT DEFAULT NULL, attempts_package_id INT DEFAULT NULL, created_at DATETIME NOT NULL, amount INT DEFAULT NULL, UNIQUE INDEX UNIQ_442E6E87D7EEBD13 (final_exam_log_id), UNIQUE INDEX UNIQ_442E6E8721540B0C (exam_log_id), INDEX IDX_442E6E87A76ED395 (user_id), INDEX IDX_442E6E8723EDC87 (subject_id), INDEX IDX_442E6E87714402BF (attempts_package_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE exam_attempt_logs ADD CONSTRAINT FK_442E6E87D7EEBD13 FOREIGN KEY (final_exam_log_id) REFERENCES final_exams_logs (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE exam_attempt_logs ADD CONSTRAINT FK_442E6E8721540B0C FOREIGN KEY (exam_log_id) REFERENCES exams_logs (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE exam_attempt_logs ADD CONSTRAINT FK_442E6E87A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE exam_attempt_logs ADD CONSTRAINT FK_442E6E8723EDC87 FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE exam_attempt_logs ADD CONSTRAINT FK_442E6E87714402BF FOREIGN KEY (attempts_package_id) REFERENCES attempts (id) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE exam_attempt_logs');
    }
}
