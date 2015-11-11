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

namespace BackBee\CoreDomainBundle\ClassContent\Repository\Element;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Element\Image;

/**
 * image repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ImageRepository extends FileRepository
{
    /**
     * Move an uploaded file to the temporary directory and update image content.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent            $file
     * @param  string                                                $newfilename
     * @param  string                                                $originalname
     * @param  string                                                $src
     *
     * @return boolean|string
     * @throws \BackBee\ClassContent\Exception\ClassContentException Occures on invalid content type provided
     */
    public function updateFile(AbstractClassContent $file, $newfilename, $originalname = null, $src = null)
    {
        if (false === ($file instanceof Image)) {
            throw new \BackBee\ClassContent\Exception\ClassContentException('Invalid content type');
        }

        if (false === $newfilename = parent::updateFile($file, $newfilename, $originalname, $src)) {
            return false;
        }

        return $newfilename;
    }
}
