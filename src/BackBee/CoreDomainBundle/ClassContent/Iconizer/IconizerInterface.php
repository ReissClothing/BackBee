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

namespace BackBee\CoreDomainBundle\ClassContent\Iconizer;

use BackBee\CoreDomain\ClassContent\AbstractContent;

/**
 * Interface implements by all content iconizer.
 * 
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
interface IconizerInterface
{
    /**
     * Returns the URL of the icon of the provided content.
     * 
     * @param  AbstractContent $content The content.
     * 
     * @return string|null              The icon URL if found.
     */
    public function getIcon(AbstractContent $content);
}
