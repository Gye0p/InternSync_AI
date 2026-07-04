<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification fields to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64) DEFAULT NULL, ADD COLUMN IF NOT EXISTS email_verification_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN IF EXISTS email_verified_at, DROP COLUMN IF EXISTS email_verification_token, DROP COLUMN IF EXISTS email_verification_token_expires_at');
    }
}
