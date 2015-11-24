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

namespace BackBee\LayoutGeneratorBundle\UidGenerator;

use BackBee\CoreDomain\Site\Site;

/**
 * Default uid generator for layout.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class DefaultGenerator implements GeneratorInterface
{
    /**
     * Generates an uid for layout
     * 
     * @param  string   $filename       The filename of the definition
     * @param  array    $data           The parsed data of the definition
     * @param  Site     $site           Optional, the site for which layout will add
     * 
     * @return string                   The generated uid
     */
    public function generateUid($name, array $data, Site $site = null)
    {
        $baseUid = ($site !== null) ? $site->getUid() : '';
        return md5($baseUid . basename($name));
    }
}