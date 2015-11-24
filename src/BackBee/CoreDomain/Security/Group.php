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

namespace BackBee\CoreDomain\Security;

use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as Serializer;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

use Doctrine\ORM\Mapping as ORM;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 * @ORM\Entity
 * @ORM\Table(name="bb_group", uniqueConstraints={@ORM\UniqueConstraint(name="UNI_IDENTIFIER",columns={"id"})})
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Group implements DomainObjectInterface
{
    /**
     * Unique identifier of the group.
     *
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected $_id;

    /**
     * Group name.
     *
     * @var string
     * @ORM\Column(type="string", name="name")
     *
     * @Serializer\Expose
     */
    protected $_name;

    /**
     * Group description.
     *
     * @var string
     * @ORM\Column(type="string", name="description", nullable=true)
     *
     * @Serializer\Expose
     */
    protected $_description;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     * @Serializer\SerializedName("users")
     * @Serializer\ReadOnly
     * @ORM\ManyToMany(targetEntity="BackBee\CoreDomain\Security\User", inversedBy="_groups", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(
     *     name="user_group",
     *     joinColumns={
     *         @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *     }
     * )
     */
    protected $_users;

    /**
     * Optional site.
     *
     * @var \BackBee\CoreDomain\Site\Site
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\Site\Site", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->_users = new ArrayCollection();
    }

    /**
     * @codeCoverageIgnore
     *
     * @return integer
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param integer $id
     *
     * @return \BackBee\Security\Group
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $name
     *
     * @return \BackBee\Security\Group
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $description
     *
     * @return \BackBee\Security\Group
     */
    public function setDescription($description)
    {
        $this->_description = $description;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function getUsers()
    {
        return $this->_users;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $users
     *
     * @return \BackBee\Security\Group
     */
    public function setUsers(ArrayCollection $users)
    {
        $this->_users = $users;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param \BackBee\CoreDomain\Security\User $user
     *
     * @return \BackBee\Security\Group
     */
    public function addUser(User $user)
    {
        $this->_users->add($user);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * Remove an from the group
     *
     * @param \BackBee\CoreDomain\Security\User $user
     *
     * @return \BackBee\Security\Group
     */
    public function removeUser(User $user)
    {
        $this->_users->removeElement($user);

        return $this;
    }

    /**
     * Returns the optional site.
     *
     * @return \BackBee\CoreDomain\Site\Site|NULL
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Sets the optional site.
     *
     * @param \BackBee\CoreDomain\Site\Site $site
     *
     * @return \BackBee\Security\Group
     * @codeCoverageIgnore
     */
    public function setSite(\BackBee\CoreDomain\Site\Site $site = null)
    {
        $this->_site = $site;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_uid")
     *
     * @return string|null
     */
    public function getSiteUid()
    {
        if (null === $this->_site) {
            return;
        }

        return $this->_site->getUid();
    }

    /**
     * @inheritDoc
     */
    public function getObjectIdentifier()
    {
        return $this->getId();
    }
}
