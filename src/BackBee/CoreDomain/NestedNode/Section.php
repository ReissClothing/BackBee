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

namespace BackBee\CoreDomain\NestedNode;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use BackBee\CoreDomain\Site\Site;

/**
 * Section object in BackBee
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 *
 * @ORM\Entity(repositoryClass="BackBee\CoreDomain\NestedNode\Repository\SectionRepository")
 * @ORM\Table(name="section",indexes={@ORM\Index(name="IDX_TREE_SECTION", columns={"uid", "root_uid", "leftnode", "rightnode"})})
 * @ORM\HasLifecycleCallbacks
 */
class Section extends AbstractNestedNode
{
    /**
     * Unique identifier of the section
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", name="uid", length=32)
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     *
     * @var \BackBee\CoreDomain\NestedNode\Section
     *
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\NestedNode\Section", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="root_uid", referencedColumnName="uid")
     */
    protected $_root;

    /**
     * The parent node.
     *
     * @var \BackBee\CoreDomain\NestedNode\Section
     *
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\NestedNode\Section", inversedBy="_children", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_uid", referencedColumnName="uid", nullable=true)
     */
    protected $_parent;

    /**
     * Descendants nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\NestedNode\Section", mappedBy="_root", fetch="EXTRA_LAZY")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\NestedNode\Section", mappedBy="_parent", fetch="EXTRA_LAZY")
     */
    protected $_children;

    /**
     * This section has not deleted children nodes.
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="has_children", options={"default"=false})
     */
    protected $_has_children = false;

    /**
     * The associated page of this section
     *
     * @var \BackBee\CoreDomain\NestedNode\Page
     *
     * @ORM\OneToOne(targetEntity="BackBee\CoreDomain\NestedNode\Page", fetch="EXTRA_LAZY", inversedBy="_mainsection", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="page_uid", referencedColumnName="uid")
     */
    protected $_page;

    /**
     * Store pages using this section.
     *
     * var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\NestedNode\Page", mappedBy="_section", fetch="EXTRA_LAZY")
     */
    protected $_pages;

    /**
     * The owner site of this section
     *
     * @var \BackBee\CoreDomain\Site\Site
     *
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\Site\Site", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * Class constructor.
     *
     * @param  string|null          $uid                The unique identifier of the section.
     * @param  array|null           $options            Initial options for the section:
     *                                                              - page      the associated page
     *                                                              - site      the owning site
     */
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);

        if (
                is_array($options) &&
                array_key_exists('page', $options) &&
                $options['page'] instanceof Page
        ) {
            $this->setPage($options['page']);
        } else {
            $this->setPage(new Page($this->getUid(), ['title' => 'Untitled', 'main_section' => $this]));
        }

        if (
                is_array($options) &&
                array_key_exists('site', $options) &&
                $options['site'] instanceof Site
        ) {
            $this->setSite($options['site']);
        }

        $this->_pages = new ArrayCollection();
    }

    /**
     * Magical cloning method.
     */
    public function __clone()
    {
        $this->_uid = md5(uniqid('', true));
        $this->_leftnode = 1;
        $this->_rightnode = $this->_leftnode + 1;
        $this->_level = 0;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_parent = null;
        $this->_root = $this;

        $this->_children = new ArrayCollection();
        $this->_descendants = new ArrayCollection();
        $this->_pages = new ArrayCollection();
    }

    /**
     * Sets the associated page for this section.
     *
     * @param  Page                 $page
     *
     * @return Section
     */
    public function setPage(Page $page)
    {
        $this->_page = $page;
        $page->setMainSection($this);

        return $this;
    }

    /**
     * Returns the associated page this section.
     *
     * @return Page
     * @codeCoverageIgnore
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     * Returns the owning pages.
     *
     * @return ArrayCollection
     * @codeCoverageIgnore
     */
    public function getPages()
    {
        return $this->_pages;
    }

    /**
     * Sets the site of this section.
     *
     * @param  Site|null            $site
     *
     * @return Section
     */
    public function setSite(Site $site = null)
    {
        $this->_site = $site;

        return $this;
    }

    /**
     * Returns the site of this section.
     *
     * @return Site
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * A section is never a leaf.
     *
     * @return boolean                                  always false
     */
    public function isLeaf()
    {
        return false;
    }

    /**
     * A section is never a leaf.
     *
     * @return boolean                                  always false
     */
    public function setHasChildren($value)
    {
        $this->_has_children = (boolean)$value;
        return $this;
    }

    /**
     * A section is never a leaf.
     *
     * @return boolean                                  always false
     */
    public function getHasChildren()
    {
        return (boolean)$this->_has_children;
    }
}
