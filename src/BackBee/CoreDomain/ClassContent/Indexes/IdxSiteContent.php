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
 * Entity class for Site-Content join table.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\CoreDomain\ClassContent\Repository\IndexationRepository")
 * @ORM\Table(name="bb_idx_site_content",indexes={
 *     @ORM\Index(name="IDX_SITE", columns={"site_uid"}),
 *     @ORM\Index(name="IDX_CONTENT_SITE", columns={"content_uid"})
 * })
 */
class IdxSiteContent
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32)
     */
    private $site_uid;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32)
     */
    private $content_uid;
}
