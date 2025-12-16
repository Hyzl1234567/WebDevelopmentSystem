<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211042707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F029395C3F3');
        $this->addSql('DROP INDEX IDX_64BF3F029395C3F3 ON coupon');
        $this->addSql('ALTER TABLE coupon ADD discount_amount DOUBLE PRECISION DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP discount, CHANGE customer_id discount_percent INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64BF3F0277153098 ON coupon (code)');
        $this->addSql('ALTER TABLE product CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE stock ADD created_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_4B365660B03A8386 ON stock (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_64BF3F0277153098 ON coupon');
        $this->addSql('ALTER TABLE coupon ADD discount DOUBLE PRECISION NOT NULL, DROP discount_amount, DROP created_at, CHANGE discount_percent customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F029395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_64BF3F029395C3F3 ON coupon (customer_id)');
        $this->addSql('ALTER TABLE product CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660B03A8386');
        $this->addSql('DROP INDEX IDX_4B365660B03A8386 ON stock');
        $this->addSql('ALTER TABLE stock DROP created_by_id, DROP created_at');
    }
}
