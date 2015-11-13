<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151111153442 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE idx_content_content (content_uid VARCHAR(32) NOT NULL, subcontent_uid VARCHAR(32) NOT NULL, INDEX IDX_SUBCONTENT (subcontent_uid), INDEX IDX_CONTENT (content_uid), PRIMARY KEY(content_uid, subcontent_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE idx_page_content (page_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_PAGE (page_uid), INDEX IDX_CONTENT_PAGE (content_uid), PRIMARY KEY(page_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE idx_site_content (site_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_SITE (site_uid), INDEX IDX_CONTENT_SITE (content_uid), PRIMARY KEY(site_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE opt_content_modified (uid VARCHAR(32) NOT NULL, `label` VARCHAR(255) DEFAULT NULL, classname VARCHAR(255) NOT NULL, node_uid VARCHAR(32) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_CLASSNAMEO (classname), INDEX IDX_NODE (node_uid), INDEX IDX_MODIFIEDO (modified), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE page CHANGE url url VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE idx_content_content');
        $this->addSql('DROP TABLE idx_page_content');
        $this->addSql('DROP TABLE idx_site_content');
        $this->addSql('DROP TABLE opt_content_modified');
        $this->addSql('ALTER TABLE page CHANGE url url VARCHAR(255) DEFAULT \'\' COLLATE utf8_unicode_ci');
    }
}
