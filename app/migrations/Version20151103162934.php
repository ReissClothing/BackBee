<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151103162934 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE layout (uid VARCHAR(32) NOT NULL, site_uid VARCHAR(32) DEFAULT NULL, `label` VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, picpath VARCHAR(255) DEFAULT NULL, parameters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_SITE (site_uid), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site (uid VARCHAR(32) NOT NULL, `label` VARCHAR(255) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, server_name VARCHAR(255) DEFAULT NULL, INDEX IDX_SERVERNAME (server_name), INDEX IDX_LABEL (`label`), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page (uid VARCHAR(32) NOT NULL, layout_uid VARCHAR(32) DEFAULT NULL, contentset VARCHAR(32) DEFAULT NULL, workflow_state VARCHAR(32) DEFAULT NULL, section_uid VARCHAR(32) DEFAULT NULL, title VARCHAR(255) NOT NULL, alttitle VARCHAR(255) DEFAULT NULL, url VARCHAR(255) NOT NULL, target VARCHAR(15) NOT NULL, redirect VARCHAR(255) DEFAULT NULL, metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\', date DATETIME DEFAULT NULL, state SMALLINT NOT NULL, publishing DATETIME DEFAULT NULL, archiving DATETIME DEFAULT NULL, level INT NOT NULL, position INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, INDEX IDX_140AB620C5A1B545 (layout_uid), INDEX IDX_140AB6204F54301 (contentset), INDEX IDX_140AB62012DDA4CF (workflow_state), INDEX IDX_140AB6208847D554 (section_uid), INDEX IDX_STATE_PAGE (state), INDEX IDX_SELECT_PAGE (level, state, publishing, archiving, modified), INDEX IDX_URL (url), INDEX IDX_MODIFIED_PAGE (modified), INDEX IDX_ARCHIVING (archiving), INDEX IDX_PUBLISHING (publishing), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page_revision (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, page_uid VARCHAR(32) DEFAULT NULL, content_uid VARCHAR(32) DEFAULT NULL, date DATETIME NOT NULL, version INT NOT NULL, INDEX IDX_EDFC12ECA76ED395 (user_id), INDEX IDX_EDFC12EC9F240E97 (page_uid), INDEX IDX_EDFC12ECE67050F3 (content_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE section (uid VARCHAR(32) NOT NULL, root_uid VARCHAR(32) DEFAULT NULL, parent_uid VARCHAR(32) DEFAULT NULL, page_uid VARCHAR(32) DEFAULT NULL, site_uid VARCHAR(32) DEFAULT NULL, has_children TINYINT(1) DEFAULT \'0\' NOT NULL, leftnode INT NOT NULL, rightnode INT NOT NULL, level INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, INDEX IDX_2D737AEF3CED4EE8 (root_uid), INDEX IDX_2D737AEF68386563 (parent_uid), UNIQUE INDEX UNIQ_2D737AEF9F240E97 (page_uid), INDEX IDX_2D737AEFA7063726 (site_uid), INDEX IDX_TREE_SECTION (uid, root_uid, leftnode, rightnode), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workflow (uid VARCHAR(32) NOT NULL, layout VARCHAR(32) DEFAULT NULL, code INT NOT NULL, `label` VARCHAR(255) NOT NULL, listener VARCHAR(255) DEFAULT NULL, INDEX IDX_65C598163A3A6BE2 (layout), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE content (uid VARCHAR(32) NOT NULL, node_uid VARCHAR(32) DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, accept LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', data LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', parameters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', maxentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', minentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created DATETIME NOT NULL, modified DATETIME NOT NULL, revision INT NOT NULL, state INT NOT NULL, classname VARCHAR(255) NOT NULL, INDEX IDX_MODIFIED (modified), INDEX IDX_STATE (state), INDEX IDX_NODEUID (node_uid), INDEX IDX_CLASSNAME (classname), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE content_has_subcontent (parent_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_390E9B2168386563 (parent_uid), INDEX IDX_390E9B21E67050F3 (content_uid), PRIMARY KEY(parent_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indexation (content_uid VARCHAR(32) NOT NULL, field VARCHAR(255) NOT NULL, owner_uid VARCHAR(32) DEFAULT NULL, value VARCHAR(255) NOT NULL, callback VARCHAR(255) DEFAULT NULL, INDEX IDX_OWNER (owner_uid), INDEX IDX_CONTENT (content_uid), INDEX IDX_VALUE (value), INDEX IDX_SEARCH (field, value), PRIMARY KEY(content_uid, field)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE revision (uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) DEFAULT NULL, classname VARCHAR(255) NOT NULL, owner VARCHAR(255) NOT NULL, comment VARCHAR(255) DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, accept LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', data LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', parameters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', maxentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', minentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created DATETIME NOT NULL, modified DATETIME NOT NULL, revision INT NOT NULL, state INT NOT NULL, INDEX IDX_CONTENT (content_uid), INDEX IDX_REVISION_CLASSNAME_1 (classname), INDEX IDX_DRAFT (owner, state), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, site_uid VARCHAR(32) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_6DC044C5A7063726 (site_uid), UNIQUE INDEX UNI_IDENTIFIER (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (group_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8F02BF9DFE54D947 (group_id), INDEX IDX_8F02BF9DA76ED395 (user_id), PRIMARY KEY(group_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, state INT DEFAULT 0 NOT NULL, activated TINYINT(1) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, api_key_public VARCHAR(255) DEFAULT NULL, api_key_private VARCHAR(255) DEFAULT NULL, api_key_enabled TINYINT(1) DEFAULT \'0\' NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, UNIQUE INDEX UNI_LOGIN (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE layout ADD CONSTRAINT FK_3A3A6BE2A7063726 FOREIGN KEY (site_uid) REFERENCES site (uid)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620C5A1B545 FOREIGN KEY (layout_uid) REFERENCES layout (uid)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB6204F54301 FOREIGN KEY (contentset) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB62012DDA4CF FOREIGN KEY (workflow_state) REFERENCES workflow (uid)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB6208847D554 FOREIGN KEY (section_uid) REFERENCES section (uid)');
        $this->addSql('ALTER TABLE page_revision ADD CONSTRAINT FK_EDFC12ECA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE page_revision ADD CONSTRAINT FK_EDFC12EC9F240E97 FOREIGN KEY (page_uid) REFERENCES page (uid)');
        $this->addSql('ALTER TABLE page_revision ADD CONSTRAINT FK_EDFC12ECE67050F3 FOREIGN KEY (content_uid) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF3CED4EE8 FOREIGN KEY (root_uid) REFERENCES section (uid)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF68386563 FOREIGN KEY (parent_uid) REFERENCES section (uid)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF9F240E97 FOREIGN KEY (page_uid) REFERENCES page (uid)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEFA7063726 FOREIGN KEY (site_uid) REFERENCES site (uid)');
        $this->addSql('ALTER TABLE workflow ADD CONSTRAINT FK_65C598163A3A6BE2 FOREIGN KEY (layout) REFERENCES layout (uid)');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A920BE247D FOREIGN KEY (node_uid) REFERENCES page (uid)');
        $this->addSql('ALTER TABLE content_has_subcontent ADD CONSTRAINT FK_390E9B2168386563 FOREIGN KEY (parent_uid) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE content_has_subcontent ADD CONSTRAINT FK_390E9B21E67050F3 FOREIGN KEY (content_uid) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE indexation ADD CONSTRAINT FK_7FE1FDFBE67050F3 FOREIGN KEY (content_uid) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE indexation ADD CONSTRAINT FK_7FE1FDFBFC50184C FOREIGN KEY (owner_uid) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE revision ADD CONSTRAINT FK_6D6315CCE67050F3 FOREIGN KEY (content_uid) REFERENCES content (uid)');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C5A7063726 FOREIGN KEY (site_uid) REFERENCES site (uid)');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id)');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620C5A1B545');
        $this->addSql('ALTER TABLE workflow DROP FOREIGN KEY FK_65C598163A3A6BE2');
        $this->addSql('ALTER TABLE layout DROP FOREIGN KEY FK_3A3A6BE2A7063726');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEFA7063726');
        $this->addSql('ALTER TABLE group DROP FOREIGN KEY FK_6DC044C5A7063726');
        $this->addSql('ALTER TABLE page_revision DROP FOREIGN KEY FK_EDFC12EC9F240E97');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF9F240E97');
        $this->addSql('ALTER TABLE content DROP FOREIGN KEY FK_FEC530A920BE247D');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB6208847D554');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF3CED4EE8');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF68386563');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB62012DDA4CF');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB6204F54301');
        $this->addSql('ALTER TABLE page_revision DROP FOREIGN KEY FK_EDFC12ECE67050F3');
        $this->addSql('ALTER TABLE content_has_subcontent DROP FOREIGN KEY FK_390E9B2168386563');
        $this->addSql('ALTER TABLE content_has_subcontent DROP FOREIGN KEY FK_390E9B21E67050F3');
        $this->addSql('ALTER TABLE indexation DROP FOREIGN KEY FK_7FE1FDFBE67050F3');
        $this->addSql('ALTER TABLE indexation DROP FOREIGN KEY FK_7FE1FDFBFC50184C');
        $this->addSql('ALTER TABLE revision DROP FOREIGN KEY FK_6D6315CCE67050F3');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DFE54D947');
        $this->addSql('ALTER TABLE page_revision DROP FOREIGN KEY FK_EDFC12ECA76ED395');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DA76ED395');
        $this->addSql('DROP TABLE layout');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE page_revision');
        $this->addSql('DROP TABLE section');
        $this->addSql('DROP TABLE workflow');
        $this->addSql('DROP TABLE content');
        $this->addSql('DROP TABLE content_has_subcontent');
        $this->addSql('DROP TABLE indexation');
        $this->addSql('DROP TABLE revision');
        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE user');
    }
}
