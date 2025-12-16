<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210085218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by_id and created_at to category and product with safe timestamp handling.';
    }

    public function up(Schema $schema): void
    {
        // --- CATEGORY TABLE ---
        $this->addSql(
            "ALTER TABLE category
             ADD created_by_id INT DEFAULT NULL,
             ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        // Populate existing rows safely
        $this->addSql(
            "UPDATE category SET created_at = NOW() WHERE created_at IS NULL"
        );

        // Enforce NOT NULL after backfill
        $this->addSql(
            "ALTER TABLE category
             MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        // Foreign key and index
        $this->addSql(
            "ALTER TABLE category
             ADD CONSTRAINT FK_64C19C1B03A8386
             FOREIGN KEY (created_by_id) REFERENCES user (id)"
        );

        $this->addSql(
            "CREATE INDEX IDX_64C19C1B03A8386 ON category (created_by_id)"
        );

        // --- ORDER TABLE ---
        $this->addSql(
            "ALTER TABLE `order`
             CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        // --- PRODUCT TABLE ---
        $this->addSql(
            "ALTER TABLE product
             ADD created_by_id INT DEFAULT NULL,
             ADD created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        // Optional: backfill product timestamps if rows exist
        $this->addSql(
            "UPDATE product SET created_at = NOW() WHERE created_at IS NULL"
        );

        $this->addSql(
            "ALTER TABLE product
             MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE product
             ADD CONSTRAINT FK_D34A04ADB03A8386
             FOREIGN KEY (created_by_id) REFERENCES user (id)"
        );

        $this->addSql(
            "CREATE INDEX IDX_D34A04ADB03A8386 ON product (created_by_id)"
        );
    }

    public function down(Schema $schema): void
    {
        // --- PRODUCT TABLE ROLLBACK ---
        $this->addSql(
            "ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386"
        );

        $this->addSql(
            "DROP INDEX IDX_D34A04ADB03A8386 ON product"
        );

        $this->addSql(
            "ALTER TABLE product DROP created_by_id, DROP created_at"
        );

        // --- CATEGORY TABLE ROLLBACK ---
        $this->addSql(
            "ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386"
        );

        $this->addSql(
            "DROP INDEX IDX_64C19C1B03A8386 ON category"
        );

        $this->addSql(
            "ALTER TABLE category DROP created_by_id, DROP created_at"
        );
    }
}
