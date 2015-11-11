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

namespace BackBee\CoreDomain\ClassContent\Indexes;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity class for optimized content table sorted by modified.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\CoreDomain\ClassContent\Repository\IndexationRepository")
 * @ORM\Table(
 *   name="opt_content_modified",
 *   indexes={
 *     @ORM\Index(name="IDX_CLASSNAMEO", columns={"classname"}),
 *     @ORM\Index(name="IDX_NODE", columns={"node_uid"}),
 *     @ORM\Index(name="IDX_MODIFIEDO", columns={"modified"})
 *   }
 * )
 */
class OptContentByModified
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid", length=32, nullable=false)
     */
    protected $_uid;

    /**
     * @var string
     * @ORM\Column(type="string", name="label", nullable=true)
     */
    protected $_label;

    /**
     * @var string
     * @ORM\Column(type="string", name="classname", nullable=false)
     */
    protected $_classname;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, name="node_uid", nullable=false)
     */
    protected $_node_uid;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created")
     */
    protected $_created;
}
