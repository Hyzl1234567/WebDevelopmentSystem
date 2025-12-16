<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209121703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, order_item_id INT DEFAULT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_794381C69395C3F3 (customer_id), UNIQUE INDEX UNIQ_794381C6E415FB15 (order_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C69395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6E415FB15 FOREIGN KEY (order_item_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C69395C3F3');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6E415FB15');
        $this->addSql('DROP TABLE review');
    }
}
