<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151106095543 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // @TODO gvf unserialize, copy structure and reserialize, classes are now out of bundle
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
//
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaDataBag","BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDataBag")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaDataBagmetadatas","BackBee\\\\CoreDomain\\\\MetaData\MetaDataBagmetadatas")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaData","BackBee\\\\CoreDomain\\\\MetaData\MetaData")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaDataname","BackBee\\\\CoreDomain\\\\MetaData\MetaDataname")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaDataattributes","BackBee\\\\CoreDomain\\\\MetaData\MetaDataattributes")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaDatais_computed","BackBee\\\\CoreDomain\\\\MetaData\MetaDatais_computed")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\MetaData\\\\MetaDatascheme","BackBee\\\\CoreDomain\\\\MetaData\MetaDatascheme")');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
//
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDataBag","BackBee\\\\MetaData\\\\MetaDataBag")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDataBagmetadatas","BackBee\\\\MetaData\MetaDataBagmetadatas")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaData","BackBee\\\\MetaData\MetaData")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDataname","BackBee\\\\MetaData\MetaDataname")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDataattributes","BackBee\\\\MetaData\MetaDataattributes")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDatais_computed","BackBee\\\\MetaData\MetaDatais_computed")');
//        $this->addSql('UPDATE page SET metadata = REPLACE(metadata, "BackBee\\\\CoreDomain\\\\MetaData\\\\MetaDatascheme","BackBee\\\\MetaData\MetaDatascheme")');
    }
}
