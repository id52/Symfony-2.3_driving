<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160812181915 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE region_place_prices (with_at TINYINT(1) NOT NULL, category_id INT NOT NULL, place_id INT NOT NULL, condition_id INT NOT NULL, active TINYINT(1) NOT NULL, price INT DEFAULT NULL, INDEX IDX_20D47DCF12469DE2 (category_id), INDEX IDX_20D47DCFDA6A219 (place_id), INDEX IDX_20D47DCF887793B6 (condition_id), PRIMARY KEY(with_at, category_id, place_id, condition_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driving_conditions (id INT AUTO_INCREMENT NOT NULL, class_service_id INT DEFAULT NULL, active TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, cond_code VARCHAR(255) NOT NULL, with_at TINYINT(1) NOT NULL, is_primary TINYINT(1) NOT NULL, description LONGTEXT NOT NULL, number_tickets INT NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_8D9268952ED2D6B2 (cond_code), INDEX IDX_8D9268955B515B44 (class_service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driving_ticket (id INT AUTO_INCREMENT NOT NULL, p_number INT DEFAULT NULL, drive_date DATETIME DEFAULT NULL, name LONGTEXT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, rating INT DEFAULT NULL, INDEX IDX_AD8C37633BBD6950 (p_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_region (user_id INT NOT NULL, region_id INT NOT NULL, INDEX IDX_6A30EA4BA76ED395 (user_id), INDEX IDX_6A30EA4B98260155 (region_id), PRIMARY KEY(user_id, region_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_regionplace (category_id INT NOT NULL, regionplace_id INT NOT NULL, INDEX IDX_4705768A12469DE2 (category_id), INDEX IDX_4705768A62FC32E3 (regionplace_id), PRIMARY KEY(category_id, regionplace_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driving_package (number INT NOT NULL, condition_id INT DEFAULT NULL, user_id INT DEFAULT NULL, rezerv_at DATETIME DEFAULT NULL, sale_at DATETIME DEFAULT NULL, received VARCHAR(255) DEFAULT NULL, INDEX IDX_453689BF887793B6 (condition_id), INDEX IDX_453689BFA76ED395 (user_id), PRIMARY KEY(number)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE documents (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, file VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, type VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, re_sent TINYINT(1) NOT NULL, comment VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_A2B072888C9F3610 (file), INDEX IDX_A2B07288A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE class_service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE region_place_prices ADD CONSTRAINT FK_20D47DCF12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE region_place_prices ADD CONSTRAINT FK_20D47DCFDA6A219 FOREIGN KEY (place_id) REFERENCES regions_places (id)');
        $this->addSql('ALTER TABLE region_place_prices ADD CONSTRAINT FK_20D47DCF887793B6 FOREIGN KEY (condition_id) REFERENCES driving_conditions (id)');
        $this->addSql('ALTER TABLE driving_conditions ADD CONSTRAINT FK_8D9268955B515B44 FOREIGN KEY (class_service_id) REFERENCES class_service (id)');
        $this->addSql('ALTER TABLE driving_ticket ADD CONSTRAINT FK_AD8C37633BBD6950 FOREIGN KEY (p_number) REFERENCES driving_package (number)');
        $this->addSql('ALTER TABLE user_region ADD CONSTRAINT FK_6A30EA4BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_region ADD CONSTRAINT FK_6A30EA4B98260155 FOREIGN KEY (region_id) REFERENCES regions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_regionplace ADD CONSTRAINT FK_4705768A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_regionplace ADD CONSTRAINT FK_4705768A62FC32E3 FOREIGN KEY (regionplace_id) REFERENCES regions_places (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE driving_package ADD CONSTRAINT FK_453689BF887793B6 FOREIGN KEY (condition_id) REFERENCES driving_conditions (id)');
        $this->addSql('ALTER TABLE driving_package ADD CONSTRAINT FK_453689BFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE regions ADD filial_not_existing TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE users ADD drive_info LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ADD final_doc_status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD drive_condition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments_logs ADD CONSTRAINT FK_8A8D45B82E4A5571 FOREIGN KEY (drive_condition_id) REFERENCES driving_conditions (id)');
        $this->addSql('CREATE INDEX IDX_8A8D45B82E4A5571 ON payments_logs (drive_condition_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE region_place_prices DROP FOREIGN KEY FK_20D47DCF887793B6');
        $this->addSql('ALTER TABLE driving_package DROP FOREIGN KEY FK_453689BF887793B6');
        $this->addSql('ALTER TABLE payments_logs DROP FOREIGN KEY FK_8A8D45B82E4A5571');
        $this->addSql('ALTER TABLE driving_ticket DROP FOREIGN KEY FK_AD8C37633BBD6950');
        $this->addSql('ALTER TABLE driving_conditions DROP FOREIGN KEY FK_8D9268955B515B44');
        $this->addSql('DROP TABLE region_place_prices');
        $this->addSql('DROP TABLE driving_conditions');
        $this->addSql('DROP TABLE driving_ticket');
        $this->addSql('DROP TABLE user_region');
        $this->addSql('DROP TABLE category_regionplace');
        $this->addSql('DROP TABLE driving_package');
        $this->addSql('DROP TABLE documents');
        $this->addSql('DROP TABLE class_service');
        $this->addSql('DROP INDEX IDX_8A8D45B82E4A5571 ON payments_logs');
        $this->addSql('ALTER TABLE payments_logs DROP drive_condition_id');
        $this->addSql('ALTER TABLE regions DROP filial_not_existing');
        $this->addSql('ALTER TABLE users DROP drive_info, DROP final_doc_status');
    }
}
