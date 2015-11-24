<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151124164442 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        $this->addSql('CREATE TABLE bb_layout (uid VARCHAR(32) NOT NULL, site_uid VARCHAR(32) DEFAULT NULL, `label` VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, picpath VARCHAR(255) DEFAULT NULL, parameters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_SITE (site_uid), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_layout  SELECT * FROM layout');
        $this->addSql('DROP TABLE layout');

        $this->addSql('CREATE TABLE bb_site (uid VARCHAR(32) NOT NULL, `label` VARCHAR(255) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, server_name VARCHAR(255) DEFAULT NULL, INDEX IDX_SERVERNAME (server_name), INDEX IDX_LABEL (`label`), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_site  SELECT * FROM site');
        $this->addSql('DROP TABLE site');

        $this->addSql('CREATE TABLE bb_keyword (uid VARCHAR(32) NOT NULL, root_uid VARCHAR(32) DEFAULT NULL, parent_uid VARCHAR(32) DEFAULT NULL, keyword VARCHAR(255) NOT NULL, leftnode INT NOT NULL, rightnode INT NOT NULL, level INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, INDEX IDX_ROOT (root_uid), INDEX IDX_PARENT (parent_uid), INDEX IDX_SELECT_KEYWORD (root_uid, leftnode, rightnode), INDEX IDX_KEYWORD (keyword), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_keyword  SELECT * FROM keyword');
        $this->addSql('DROP TABLE keyword');

        $this->addSql('CREATE TABLE bb_keywords_contents (keyword_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_8D3F459ABD3B0308 (keyword_uid), INDEX IDX_8D3F459AE67050F3 (content_uid), PRIMARY KEY(keyword_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_keywords_contents  SELECT * FROM keywords_contents');
        $this->addSql('DROP TABLE keywords_contents');

        $this->addSql('CREATE TABLE bb_page (uid VARCHAR(32) NOT NULL, layout_uid VARCHAR(32) DEFAULT NULL, contentset VARCHAR(32) DEFAULT NULL, workflow_state VARCHAR(32) DEFAULT NULL, section_uid VARCHAR(32) DEFAULT NULL, title VARCHAR(255) NOT NULL, alttitle VARCHAR(255) DEFAULT NULL, url VARCHAR(255) NOT NULL, target VARCHAR(15) NOT NULL, redirect VARCHAR(255) DEFAULT NULL, metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\', date DATETIME DEFAULT NULL, state SMALLINT NOT NULL, publishing DATETIME DEFAULT NULL, archiving DATETIME DEFAULT NULL, level INT NOT NULL, position INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, INDEX IDX_4B741DC5C5A1B545 (layout_uid), INDEX IDX_4B741DC54F54301 (contentset), INDEX IDX_4B741DC512DDA4CF (workflow_state), INDEX IDX_4B741DC58847D554 (section_uid), INDEX IDX_STATE_PAGE (state), INDEX IDX_SELECT_PAGE (level, state, publishing, archiving, modified), INDEX IDX_URL (url), INDEX IDX_MODIFIED_PAGE (modified), INDEX IDX_ARCHIVING (archiving), INDEX IDX_PUBLISHING (publishing), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_page  SELECT * FROM page');
        $this->addSql('DROP TABLE page');

        $this->addSql('CREATE TABLE bb_page_revision (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, page_uid VARCHAR(32) DEFAULT NULL, content_uid VARCHAR(32) DEFAULT NULL, date DATETIME NOT NULL, version INT NOT NULL, INDEX IDX_2BF53CC6A76ED395 (user_id), INDEX IDX_2BF53CC69F240E97 (page_uid), INDEX IDX_2BF53CC6E67050F3 (content_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_page_revision  SELECT * FROM page_revision');
        $this->addSql('DROP TABLE page_revision');

        $this->addSql('CREATE TABLE bb_section (uid VARCHAR(32) NOT NULL, root_uid VARCHAR(32) DEFAULT NULL, parent_uid VARCHAR(32) DEFAULT NULL, page_uid VARCHAR(32) DEFAULT NULL, site_uid VARCHAR(32) DEFAULT NULL, has_children TINYINT(1) DEFAULT \'0\' NOT NULL, leftnode INT NOT NULL, rightnode INT NOT NULL, level INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, INDEX IDX_781451E23CED4EE8 (root_uid), INDEX IDX_781451E268386563 (parent_uid), UNIQUE INDEX UNIQ_781451E29F240E97 (page_uid), INDEX IDX_781451E2A7063726 (site_uid), INDEX IDX_TREE_SECTION (uid, root_uid, leftnode, rightnode), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_section  SELECT * FROM section');
        $this->addSql('DROP TABLE section');

        $this->addSql('CREATE TABLE bb_workflow (uid VARCHAR(32) NOT NULL, layout VARCHAR(32) DEFAULT NULL, code INT NOT NULL, `label` VARCHAR(255) NOT NULL, listener VARCHAR(255) DEFAULT NULL, INDEX IDX_1B2183803A3A6BE2 (layout), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_workflow  SELECT * FROM workflow');
        $this->addSql('DROP TABLE workflow');

        $this->addSql('CREATE TABLE bb_content_has_subcontent (parent_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_90045B0268386563 (parent_uid), INDEX IDX_90045B02E67050F3 (content_uid), PRIMARY KEY(parent_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_content_has_subcontent  SELECT * FROM content_has_subcontent');
        $this->addSql('DROP TABLE content_has_subcontent');

        $this->addSql('CREATE TABLE bb_indexation (content_uid VARCHAR(32) NOT NULL, field VARCHAR(255) NOT NULL, owner_uid VARCHAR(32) DEFAULT NULL, value VARCHAR(255) NOT NULL, callback VARCHAR(255) DEFAULT NULL, INDEX IDX_OWNER (owner_uid), INDEX IDX_CONTENT (content_uid), INDEX IDX_VALUE (value), INDEX IDX_SEARCH (field, value), PRIMARY KEY(content_uid, field)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_indexation  SELECT * FROM indexation');
        $this->addSql('DROP TABLE indexation');

        $this->addSql('CREATE TABLE bb_idx_content_content (content_uid VARCHAR(32) NOT NULL, subcontent_uid VARCHAR(32) NOT NULL, INDEX IDX_SUBCONTENT (subcontent_uid), INDEX IDX_CONTENT (content_uid), PRIMARY KEY(content_uid, subcontent_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_idx_content_content  SELECT * FROM idx_content_content');
        $this->addSql('DROP TABLE idx_content_content');

        $this->addSql('CREATE TABLE bb_idx_page_content (page_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_PAGE (page_uid), INDEX IDX_CONTENT_PAGE (content_uid), PRIMARY KEY(page_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_idx_page_content  SELECT * FROM idx_page_content');
        $this->addSql('DROP TABLE idx_page_content');

        $this->addSql('CREATE TABLE bb_idx_site_content (site_uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) NOT NULL, INDEX IDX_SITE (site_uid), INDEX IDX_CONTENT_SITE (content_uid), PRIMARY KEY(site_uid, content_uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_idx_site_content  SELECT * FROM idx_site_content');
        $this->addSql('DROP TABLE idx_site_content');

        $this->addSql('CREATE TABLE bb_revision (uid VARCHAR(32) NOT NULL, content_uid VARCHAR(32) DEFAULT NULL, classname VARCHAR(255) NOT NULL, owner VARCHAR(255) NOT NULL, comment VARCHAR(255) DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, accept LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', data LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', parameters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', maxentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', minentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created DATETIME NOT NULL, modified DATETIME NOT NULL, revision INT NOT NULL, state INT NOT NULL, INDEX IDX_CONTENT (content_uid), INDEX IDX_REVISION_CLASSNAME_1 (classname), INDEX IDX_DRAFT (owner, state), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_revision  SELECT * FROM revision');
        $this->addSql('DROP TABLE revision');

        $this->addSql('CREATE TABLE bb_group (id INT AUTO_INCREMENT NOT NULL, site_uid VARCHAR(32) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_BDFF2C99A7063726 (site_uid), UNIQUE INDEX UNI_IDENTIFIER (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_group  SELECT * FROM `group`');
        $this->addSql('DROP TABLE `group`');

        $this->addSql('CREATE TABLE bb_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, state INT DEFAULT 0 NOT NULL, activated TINYINT(1) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, api_key_public VARCHAR(255) DEFAULT NULL, api_key_private VARCHAR(255) DEFAULT NULL, api_key_enabled TINYINT(1) DEFAULT \'0\' NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, UNIQUE INDEX UNIusername (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_user  SELECT * FROM user');
        $this->addSql('DROP TABLE user');

        $this->addSql('CREATE TABLE bb_content (uid VARCHAR(32) NOT NULL, node_uid VARCHAR(32) DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, accept LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', data LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', parameters LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', maxentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', minentry LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created DATETIME NOT NULL, modified DATETIME NOT NULL, revision INT NOT NULL, state INT NOT NULL, classname VARCHAR(255) NOT NULL, INDEX IDX_MODIFIED (modified), INDEX IDX_STATE (state), INDEX IDX_NODEUID (node_uid), INDEX IDX_CLASSNAME (classname), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_content  SELECT * FROM `content`');
        $this->addSql('DROP TABLE content');

        $this->addSql('CREATE TABLE bb_opt_content_modified (uid VARCHAR(32) NOT NULL, `label` VARCHAR(255) DEFAULT NULL, classname VARCHAR(255) NOT NULL, node_uid VARCHAR(32) NOT NULL, modified DATETIME NOT NULL, created DATETIME NOT NULL, INDEX IDX_CLASSNAMEO (classname), INDEX IDX_NODE (node_uid), INDEX IDX_MODIFIEDO (modified), PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO bb_opt_content_modified  SELECT * FROM `opt_content_modified`');
        $this->addSql('DROP TABLE opt_content_modified');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
