<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151124172349 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bb_layout ADD CONSTRAINT FK_58374955A7063726 FOREIGN KEY (site_uid) REFERENCES bb_site (uid)');
        $this->addSql('ALTER TABLE bb_keyword ADD CONSTRAINT FK_FF45A363CED4EE8 FOREIGN KEY (root_uid) REFERENCES bb_keyword (uid) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE bb_keyword ADD CONSTRAINT FK_FF45A3668386563 FOREIGN KEY (parent_uid) REFERENCES bb_keyword (uid)');
        $this->addSql('ALTER TABLE bb_keywords_contents ADD CONSTRAINT FK_8D3F459ABD3B0308 FOREIGN KEY (keyword_uid) REFERENCES bb_keyword (uid)');
        $this->addSql('ALTER TABLE bb_keywords_contents ADD CONSTRAINT FK_8D3F459AE67050F3 FOREIGN KEY (content_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_page ADD CONSTRAINT FK_4B741DC5C5A1B545 FOREIGN KEY (layout_uid) REFERENCES bb_layout (uid)');
        $this->addSql('ALTER TABLE bb_page ADD CONSTRAINT FK_4B741DC54F54301 FOREIGN KEY (contentset) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_page ADD CONSTRAINT FK_4B741DC512DDA4CF FOREIGN KEY (workflow_state) REFERENCES bb_workflow (uid)');
        $this->addSql('ALTER TABLE bb_page ADD CONSTRAINT FK_4B741DC58847D554 FOREIGN KEY (section_uid) REFERENCES bb_section (uid)');
        $this->addSql('ALTER TABLE bb_page_revision ADD CONSTRAINT FK_2BF53CC6A76ED395 FOREIGN KEY (user_id) REFERENCES bb_user (id)');
        $this->addSql('ALTER TABLE bb_page_revision ADD CONSTRAINT FK_2BF53CC69F240E97 FOREIGN KEY (page_uid) REFERENCES bb_page (uid)');
        $this->addSql('ALTER TABLE bb_page_revision ADD CONSTRAINT FK_2BF53CC6E67050F3 FOREIGN KEY (content_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_section ADD CONSTRAINT FK_781451E23CED4EE8 FOREIGN KEY (root_uid) REFERENCES bb_section (uid)');
        $this->addSql('ALTER TABLE bb_section ADD CONSTRAINT FK_781451E268386563 FOREIGN KEY (parent_uid) REFERENCES bb_section (uid)');
        $this->addSql('ALTER TABLE bb_section ADD CONSTRAINT FK_781451E29F240E97 FOREIGN KEY (page_uid) REFERENCES bb_page (uid)');
        $this->addSql('ALTER TABLE bb_section ADD CONSTRAINT FK_781451E2A7063726 FOREIGN KEY (site_uid) REFERENCES bb_site (uid)');
        $this->addSql('ALTER TABLE bb_workflow ADD CONSTRAINT FK_1B2183803A3A6BE2 FOREIGN KEY (layout) REFERENCES bb_layout (uid)');
        $this->addSql('ALTER TABLE bb_content ADD CONSTRAINT FK_ABA21BA420BE247D FOREIGN KEY (node_uid) REFERENCES bb_page (uid)');
        $this->addSql('ALTER TABLE bb_content_has_subcontent ADD CONSTRAINT FK_90045B0268386563 FOREIGN KEY (parent_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_content_has_subcontent ADD CONSTRAINT FK_90045B02E67050F3 FOREIGN KEY (content_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_indexation ADD CONSTRAINT FK_D29F676FE67050F3 FOREIGN KEY (content_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_indexation ADD CONSTRAINT FK_D29F676FFC50184C FOREIGN KEY (owner_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_revision ADD CONSTRAINT FK_13870E5AE67050F3 FOREIGN KEY (content_uid) REFERENCES bb_content (uid)');
        $this->addSql('ALTER TABLE bb_group ADD CONSTRAINT FK_BDFF2C99A7063726 FOREIGN KEY (site_uid) REFERENCES bb_site (uid)');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DA76ED395');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DFE54D947');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DA76ED395 FOREIGN KEY (user_id) REFERENCES bb_user (id)');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES bb_group (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bb_content DROP FOREIGN KEY FK_ABA21BA420BE247D');
        $this->addSql('ALTER TABLE bb_content_has_subcontent DROP FOREIGN KEY FK_90045B0268386563');
        $this->addSql('ALTER TABLE bb_content_has_subcontent DROP FOREIGN KEY FK_90045B02E67050F3');
        $this->addSql('ALTER TABLE bb_group DROP FOREIGN KEY FK_BDFF2C99A7063726');
        $this->addSql('ALTER TABLE bb_indexation DROP FOREIGN KEY FK_D29F676FE67050F3');
        $this->addSql('ALTER TABLE bb_indexation DROP FOREIGN KEY FK_D29F676FFC50184C');
        $this->addSql('ALTER TABLE bb_keyword DROP FOREIGN KEY FK_FF45A363CED4EE8');
        $this->addSql('ALTER TABLE bb_keyword DROP FOREIGN KEY FK_FF45A3668386563');
        $this->addSql('ALTER TABLE bb_keywords_contents DROP FOREIGN KEY FK_8D3F459ABD3B0308');
        $this->addSql('ALTER TABLE bb_keywords_contents DROP FOREIGN KEY FK_8D3F459AE67050F3');
        $this->addSql('ALTER TABLE bb_layout DROP FOREIGN KEY FK_58374955A7063726');
        $this->addSql('ALTER TABLE bb_page DROP FOREIGN KEY FK_4B741DC5C5A1B545');
        $this->addSql('ALTER TABLE bb_page DROP FOREIGN KEY FK_4B741DC54F54301');
        $this->addSql('ALTER TABLE bb_page DROP FOREIGN KEY FK_4B741DC512DDA4CF');
        $this->addSql('ALTER TABLE bb_page DROP FOREIGN KEY FK_4B741DC58847D554');
        $this->addSql('ALTER TABLE bb_page_revision DROP FOREIGN KEY FK_2BF53CC6A76ED395');
        $this->addSql('ALTER TABLE bb_page_revision DROP FOREIGN KEY FK_2BF53CC69F240E97');
        $this->addSql('ALTER TABLE bb_page_revision DROP FOREIGN KEY FK_2BF53CC6E67050F3');
        $this->addSql('ALTER TABLE bb_revision DROP FOREIGN KEY FK_13870E5AE67050F3');
        $this->addSql('ALTER TABLE bb_section DROP FOREIGN KEY FK_781451E23CED4EE8');
        $this->addSql('ALTER TABLE bb_section DROP FOREIGN KEY FK_781451E268386563');
        $this->addSql('ALTER TABLE bb_section DROP FOREIGN KEY FK_781451E29F240E97');
        $this->addSql('ALTER TABLE bb_section DROP FOREIGN KEY FK_781451E2A7063726');
        $this->addSql('ALTER TABLE bb_workflow DROP FOREIGN KEY FK_1B2183803A3A6BE2');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DFE54D947');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DA76ED395');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id)');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
