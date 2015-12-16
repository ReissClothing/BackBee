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

use BackBee\CoreDomain\Utils\Numeric;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

use BackBee\CoreDomain\Security\Acl\Domain\AbstractObjectIdentifiable;

/**
 * Abstract class for nested node object in BackBee.
 *
 * A nested node is used to build nested tree.
 * Nested nodes are used by:
 *
 * * \BackBee\CoreDomain\NestedNode\Page        The page tree of a website
 * * \BackBee\NestedNode\Mediafolder The folder tree of the library
 * * \BackBee\CoreDomain\NestedNode\KeyWord     The keywords trees
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\MappedSuperclass
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractNestedNode extends AbstractObjectIdentifiable
{
    /**
     * Unique identifier of the node.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32, name="uid")
     *
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     * @var \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @Serializer\Exclude
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    protected $_parent;

    /**
     * The nested node left position.
     *
     * @var int
     * @ORM\Column(type="integer", name="leftnode", nullable=false)
     */
    protected $_leftnode;

    /**
     * The nested node right position.
     *
     * @var int
     * @ORM\Column(type="integer", name="rightnode", nullable=false)
     */
    protected $_rightnode;

    /**
     * The nested node level in the tree.
     *
     * @var int
     * @ORM\Column(type="integer", name="level", nullable=false)
     */
    protected $_level;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created", nullable=false)
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     */
    protected $_modified;

    /**
     * Descendants nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $_children;

    /**
     * Properties ignored while unserializing object.
     *
     * @var array
     */
    protected $_unserialized_ignored = array('_created', '_modified');

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the node
     * @param array  $options Initial options for the node
     */
    public function __construct($uid = null, $options = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->_leftnode = 1;
        $this->_rightnode = $this->_leftnode + 1;
        $this->_level = 0;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_root = $this;

        $this->_children = new ArrayCollection();
        $this->_descendants = new ArrayCollection();
    }

    /**
     * Returns te unique identifier.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the root node.
     *
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function getRoot()
    {
        return $this->_root;
    }

    /**
     * Returns the parent node, NULL if this node is root.
     *
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode|NULL
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Returns the nested node left position.
     *
     * @return int
     */
    public function getLeftnode()
    {
        return $this->_leftnode;
    }

    /**
     * Returns the nested node right position.
     *
     * @return int
     */
    public function getRightnode()
    {
        return $this->_rightnode;
    }

    /**
     * Returns the weight of the node, ie the number of descendants plus itself.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->_rightnode - $this->_leftnode + 1;
    }

    /**
     * Returns the level of the node in the tree, 0 for root node.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * Returns the creation date.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the last modified date.
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Returns a collection of descendant nodes.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDescendants()
    {
        return $this->_descendants;
    }

    /**
     * Returns a collection of direct children nodes.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildren()
    {
        return $this->_children;
    }

    /**
     * Is the node is a root ?
     *
     * @return Boolean TRUE if the node is root of tree, FALSE otherwise
     * /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_root")
     *
     */
    public function isRoot()
    {
        return (1 === $this->_leftnode && null === $this->_parent);
    }

    /**
     * Is the node is a leaf ?
     *
     * @return Boolean TRUE if the node if a leaf of tree, FALSE otherwise
     */
    public function isLeaf()
    {
        return (1 === ($this->_rightnode - $this->_leftnode));
    }

    /**
     * Is this node is an ancestor of the provided one ?
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  Boolean                         $strict Optional, if TRUE (default) this node is excluded of ancestors list
     *
     * @return Boolean                         TRUE if this node is an anscestor or provided node, FALSE otherwise
     */
    public function isAncestorOf(AbstractNestedNode $node, $strict = true)
    {
        if (true === $strict) {
            return (($node->getRoot() === $this->getRoot()) && ($node->getLeftnode() > $this->getLeftnode()) && ($node->getRightnode() < $this->getRightnode()));
        } else {
            return (($node->getRoot() === $this->getRoot()) && ($node->getLeftnode() >= $this->getLeftnode()) && ($node->getRightnode() <= $this->getRightnode()));
        }
    }

    /**
     * Is this node is a descendant of the provided one ?
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  Boolean                         $strict Optional, if TRUE (default) this node is excluded of descendants list
     *
     * @return Boolean                         TRUE if this node is a descendant or provided node, FALSE otherwise
     */
    public function isDescendantOf(AbstractNestedNode $node, $strict = true)
    {
        if (true === $strict) {
            return (($this->getLeftnode() > $node->getLeftnode()) && ($this->getRightnode() < $node->getRightnode()) && ($this->getRoot() === $node->getRoot()));
        } else {
            return (($this->getLeftnode() >= $node->getLeftnode()) && ($this->getRightnode() <= $node->getRightnode()) && ($this->getRoot() === $node->getRoot()));
        }
    }

    /**
     * Determine if current node has children.
     *
     * @return boolean
     */
    public function hasChildren()
    {
        return ($this->getRightnode() - $this->getLeftnode()) > 1;
    }

    /**
     * Sets the unique identifier of the node.
     *
     * @param string $uid
     */
    public function setUid($uid)
    {
        $this->_uid = $uid;

        return $this;
    }

    /**
     * Sets the root node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $root
     *
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function setRoot(AbstractNestedNode $root)
    {
        $this->_root = $root;

        return $this;
    }

    /**
     * Sets the parent node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $parent
     *
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function setParent(AbstractNestedNode $parent)
    {
        $this->_parent = $parent;

        return $this;
    }

    /**
     * Sets the left position.
     * @param  int                                         $leftnode
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the value can not be cast to positive integer
     */
    public function setLeftnode($leftnode)
    {
        if (false === Numeric::isPositiveInteger($leftnode)) {
            throw new \InvalidArgumentException('A nested node position must be a strictly positive integer.');
        }

        $this->_leftnode = $leftnode;

        return $this;
    }

    /**
     * Sets the right position.
     *
     * @param  int                                         $rightnode
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the value can not be cast to positive integer
     */
    public function setRightnode($rightnode)
    {
        if (false === Numeric::isPositiveInteger($rightnode)) {
            throw new InvalidArgumentException('A nested node position must be a strictly positive integer.');
        }

        $this->_rightnode = $rightnode;

        return $this;
    }

    /**
     * Sets the level.
     *
     * @param  type                                        $level
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the value can not be cast to positive integer
     */
    public function setLevel($level)
    {
        if (false === Numeric::isPositiveInteger($level, false)) {
            throw new InvalidArgumentException('A nested level must be a positive integer.');
        }

        $this->_level = $level;

        return $this;
    }

    /**
     * Sets the creation date.
     *
     * @param  \DateTime                       $created
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function setCreated(\DateTime $created)
    {
        $this->_created = $created;

        return $this;
    }

    /**
     * Sets the last modified date.
     *
     * @param  \DateTime                       $modified
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function setModified($modified)
    {
        $this->_modified = $modified;

        return $this;
    }
}
