<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151106111845 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE keyword (uid VARCHAR(32) NOT NULL, root_uid VARCHAR(32) DEFAULT NULL, parent_uid VARCHAR(32) DEFAULT NULL, keyword VARCHAR(255) NOT NULL, leftnode INT NOT NULL, rightnode INT NOT NULL, level INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, INDEX IDX_ROOT (root_uid), INDEX IDX_PARENT (parent_uid), INDEX IDX_SELECT_KEYWORD (root_uid, leftnode, rightnode), INDEX IDX_KEYWORD (keyword), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE keywords_contents (keyword_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_D3A6BDE6BD3B0308 (keyword_uid), INDEX IDX_D3A6BDE6E67050F3 (content_uid), PRIMARY KEY(keyword_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE keyword ADD CONSTRAINT FK_5A93713B3CED4EE8 FOREIGN KEY (root_uid) REFERENCES keyword (uid) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE keyword ADD CONSTRAINT FK_5A93713B68386563 FOREIGN KEY (parent_uid) REFERENCES keyword (uid)');
        $this->addSql('ALTER TABLE keywords_contents ADD CONSTRAINT FK_D3A6BDE6BD3B0308 FOREIGN KEY (keyword_uid) REFERENCES keyword (uid)');
        $this->addSql('ALTER TABLE keywords_contents ADD CONSTRAINT FK_D3A6BDE6E67050F3 FOREIGN KEY (content_uid) REFERENCES content (uid)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE keyword DROP FOREIGN KEY FK_5A93713B3CED4EE8');
        $this->addSql('ALTER TABLE keyword DROP FOREIGN KEY FK_5A93713B68386563');
        $this->addSql('ALTER TABLE keywords_contents DROP FOREIGN KEY FK_D3A6BDE6BD3B0308');
        $this->addSql('DROP TABLE keyword');
        $this->addSql('DROP TABLE keywords_contents');
    }
}
