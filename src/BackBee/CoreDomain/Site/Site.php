<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\CoreDomain\Site;

//use BackBee\Installer\Annotation as BB;
use BackBee\CoreDomain\Security\Acl\Domain\AbstractObjectIdentifiable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * A BackBee website entity.
 *
 * A website should be associated to:
 *
 * * a collection of available layouts
 * * a collection of default metadata sets
 *
 * @category    BackBee
 *
 * @IgnoreAnnotation("BB\Fixtures")
 * @IgnoreAnnotation("BB\Fixture")
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 *
 * @ORM\Entity(repositoryClass="BackBee\Site\Repository\SiteRepository")
 * @ORM\Table(name="site", indexes={
 *     @ORM\Index(name="IDX_SERVERNAME", columns={"server_name"}),
 *     @ORM\Index(name="IDX_LABEL", columns={"label"})})
 * @BB\Fixtures(qty=1)
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Site extends AbstractObjectIdentifiable
{
    /**
     * The unique identifier of this website.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32, name="uid")
     * @BB\Fixture(type="md5")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("id")
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The label of this website.
     *
     * @var string
     * @ORM\Column(type="string", name="label", nullable=false)
     * @BB\Fixture(type="domainWord")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("label")
     * @Serializer\Type("string")
     *
     */
    protected $_label;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created", nullable=false)
     * @BB\Fixture(type="dateTime")
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     * @BB\Fixture(type="dateTime")
     */
    protected $_modified;

    /**
     * The optional server name.
     *
     * @var string
     * @ORM\Column(type="string", name="server_name", nullable=true)
     * @BB\Fixture(type="domainWord")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("server_name")
     * @Serializer\Type("string")
     *
     */
    protected $_server_name;

    /**
     * The default extension used by the site.
     *
     * @var string
     */
    protected $_default_ext = '.html';

    /**
     * The collection of layouts available for this site.
     *
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\Site\Layout", mappedBy="_site", fetch="EXTRA_LAZY")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("layouts")
     * @Serializer\Type("ArrayCollection<string, BackBee\Site\Layout>")
     */
    protected $_layouts;

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the site.
     * @param array  $options Initial options for the content:
     *                        - label      the default label
     */
    public function __construct($uid = null, $options = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();

        $this->_layouts = new ArrayCollection();

        if (
                true === is_array($options) &&
                true === array_key_exists('label', $options)
        ) {
            $this->setLabel($options['label']);
        }
    }

    /**
     * Returns the unique identifier.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the label.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns the associated server name.
     *
     * @codeCoverageIgnore
     *
     * @return string|NULL
     */
    public function getServerName()
    {
        return $this->_server_name;
    }

    /**
     * Return the default defined extension.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getDefaultExtension()
    {
        return $this->_default_ext;
    }

    /**
     * Returns the collection of layouts available for this website.
     *
     * @codeCoverageIgnore
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getLayouts()
    {
        return $this->_layouts;
    }

    /**
     * @param \BackBee\Site\Layout $layout
     */
    public function addLayout(Layout $layout)
    {
        $this->_layouts[] = $layout;
    }

    /**
     * Sets the label of the website.
     *
     * @param string $label
     *
     * @return \BackBee\CoreDomain\Site\Site
     */
    public function setLabel($label)
    {
        $this->_label = $label;

        return $this;
    }

    /**
     * Sets the server name.
     *
     * @param string $serverName
     *
     * @return \BackBee\CoreDomain\Site\Site
     */
    public function setServerName($serverName)
    {
        $this->_server_name = $serverName;

        return $this;
    }
}
