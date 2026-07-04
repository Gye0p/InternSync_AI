<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create core INTERNSYNC tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `user` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS ojt_assignment (id INT AUTO_INCREMENT NOT NULL, company_name VARCHAR(255) NOT NULL, required_hours INT NOT NULL, hours_completed DOUBLE PRECISION NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, student_id INT NOT NULL, supervisor_id INT NOT NULL, INDEX IDX_201B41C5CB944F1A (student_id), INDEX IDX_201B41C519E9AC5F (supervisor_id), PRIMARY KEY (id), CONSTRAINT FK_201B41C5CB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id), CONSTRAINT FK_201B41C519E9AC5F FOREIGN KEY (supervisor_id) REFERENCES `user` (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS daily_log (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, content LONGTEXT NOT NULL, ai_feedback JSON DEFAULT NULL, skill_tags JSON DEFAULT NULL, clarity_score INT DEFAULT NULL, status VARCHAR(20) NOT NULL, supervisor_comment LONGTEXT DEFAULT NULL, hours_worked DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, assignment_id INT NOT NULL, INDEX IDX_8D0D8EA9D19302F8 (assignment_id), PRIMARY KEY (id), CONSTRAINT FK_8D0D8EA9D19302F8 FOREIGN KEY (assignment_id) REFERENCES ojt_assignment (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS certificate (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(500) NOT NULL, generated_at DATETIME NOT NULL, assignment_id INT NOT NULL, UNIQUE INDEX UNIQ_219CDA4AD19302F8 (assignment_id), PRIMARY KEY (id), CONSTRAINT FK_219CDA4AD19302F8 FOREIGN KEY (assignment_id) REFERENCES ojt_assignment (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS certificate');
        $this->addSql('DROP TABLE IF EXISTS daily_log');
        $this->addSql('DROP TABLE IF EXISTS ojt_assignment');
        $this->addSql('DROP TABLE IF EXISTS `user`');
    }
}