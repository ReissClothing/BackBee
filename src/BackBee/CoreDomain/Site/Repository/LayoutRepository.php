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

namespace BackBee\CoreDomain\Site\Repository;

use BackBee\BBApplication;
use BackBee\CoreDomain\Site\Layout;
use BackBee\Utils\File\File;

use Doctrine\ORM\EntityRepository;
use Exception;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class LayoutRepository extends EntityRepository
{
    /**
     * Draw a filled rect on image.
     *
     * @access private
     *
     * @param ressource $image      The image ressource
     * @param array     $clip       The clip rect to draw
     * @param int       $background The background color
     * @param boolean   $nowpadding If true don't insert a right padding
     * @param boolean   $nohpadding If true don't insert a bottom padding
     */
    private function drawRect(&$image, $clip, $background, $nowpadding = true, $nohpadding = true)
    {
        imagefilledrectangle($image, $clip[0], $clip[1], $clip[0] + $clip[2] - (!$nowpadding * 1), $clip[1] + $clip[3] - (!$nohpadding * 1), $background);
    }

    /**
     * Draw a final layout zone on its thumbnail.
     *
     * @access private
     *
     * @param ressource $thumbnail  The thumbnail ressource
     * @param DOMNode   $node       The current node zone
     * @param array     $clip       The clip rect to draw
     * @param int       $background The background color
     * @param int       $gridcolumn The number of columns in the grid
     * @param boolean   $lastChild  True if the current node is the last child of its parent node
     *
     * @return int The new X axis position;
     */
    private function drawThumbnailZone(&$thumbnail, $node, $clip, $background, $gridcolumn, $lastChild = false)
    {
        $x = $clip[0];
        $y = $clip[1];
        $width = $clip[2];
        $height = $clip[3];

        if (null !== $spansize = preg_replace('/[^0-9]+/', '', $node->getAttribute('class'))) {
            $width = floor($width * $spansize / $gridcolumn);
        }

        if (false !== strpos($node->getAttribute('class'), 'Child')) {
            $height = floor($height / 2);
        }

        if (!$node->hasChildNodes()) {
            $this->drawRect($thumbnail, array($x, $y, $width, $height), $background, ($width == $clip[2] || strpos($node->getAttribute('class'), 'hChild')), $lastChild);

            return $width + 2;
        }

        foreach ($node->childNodes as $child) {
            if (is_a($child, 'DOMText')) {
                continue;
            }

            if ('clear' == $child->getAttribute('class')) {
                $x = $clip[0];
                $y = $clip[1] + floor($height / 2) + 2;
                continue;
            }

            $x += $this->drawThumbnailZone($thumbnail, $child, array($x, $y, $clip[2], $height), $background, $gridcolumn, $node->isSameNode($node->parentNode->lastChild));
        }

        return $x + $width - 2;
    }

    /**
     * Generate a layout thumbnail according to the configuration.
     *
     * @access public
     *
     * @param Layout        $layout The layout to treate
     * @param BBApplication $app    The current instance of BBApplication
     *
     * @return mixed FALSE if something wrong, the ressource path of the thumbnail elsewhere
     */
    public function generateThumbnail(Layout $layout, BBApplication $app)
    {
        // Is the layout valid ?
        if (!$layout->isValid()) {
            return false;
        }

        // Is some layout configuration existing ?
        if (null === $app->getConfig()->getSection('layout')) {
            return false;
        }
        $layoutconfig = $app->getConfig()->getSection('layout');

        // Is some thumbnail configuration existing ?
        if (!isset($layoutconfig['thumbnail'])) {
            return false;
        }
        $thumbnailconfig = $layoutconfig['thumbnail'];

        // Is gd available ?
        if (!function_exists('gd_info')) {
            return false;
        }
        $gd_info = gd_info();

        // Is the selected format supported by gd ?
        if (!isset($thumbnailconfig['format'])) {
            return false;
        }
        if (true !== $gd_info[strtoupper($thumbnailconfig['format']).' Support']) {
            return false;
        }

        // Is the template file existing ?
        if (!isset($thumbnailconfig['template'])) {
            return false;
        }
        $templatefile = $thumbnailconfig['template'];
        $thumbnaildir = dirname($templatefile);
        File::resolveFilepath($templatefile, null, array('include_path' => $app->getResourceDir()));
        if (false === file_exists($templatefile) || false === is_readable($templatefile)) {
            return false;
        }

        try {
            $gd_function = 'imagecreatefrom'.strtolower($thumbnailconfig['format']);
            $thumbnail = $gd_function($templatefile);
            $thumbnailfile = $thumbnaildir.'/'.$layout->getUid().'.'.strtolower($thumbnailconfig['format']);

            // Is a background color existing ?
            if (!isset($thumbnailconfig['background']) || !is_array($thumbnailconfig['background']) || 3 != count($thumbnailconfig['background'])) {
                return false;
            }
            $background = imagecolorallocate($thumbnail, $thumbnailconfig['background'][0], $thumbnailconfig['background'][1], $thumbnailconfig['background'][2]);

            // Is a clipping zone existing ?
            if (!isset($thumbnailconfig['clip']) || !is_array($thumbnailconfig['clip']) || 4 != count($thumbnailconfig['clip'])) {
                return false;
            }

            $gridcolumn = 12;
            if (null !== $lessconfig = $app->getConfig()->getSection('less')) {
                if (isset($lessconfig['gridcolumn'])) {
                    $gridcolumn = $lessconfig['gridcolumn'];
                }
            }

            $domlayout = $layout->getDomDocument();
            if (!$domlayout->hasChildNodes() || !$domlayout->firstChild->hasChildNodes()) {
                $this->drawRect($thumbnail, $thumbnailconfig['clip'], $background);
            } else {
                $this->drawThumbnailZone($thumbnail, $domlayout->firstChild, $thumbnailconfig['clip'], $background, $gridcolumn);
            }

            imagesavealpha($thumbnail, true);

            $thumbnaildir = dirname(File::normalizePath($app->getCurrentResourceDir().'/'.$thumbnailfile));
            if (false === is_dir($thumbnaildir)) {
                mkdir($thumbnaildir, 0755, true);
            }

            imagepng($thumbnail, File::normalizePath($app->getCurrentResourceDir().'/'.$thumbnailfile));
        } catch (\Exception $e) {
            return false;
        }

        $layout->setPicPath($thumbnailfile);

        return $layout->getPicPath();
    }

    public function removeThumbnail(Layout $layout, BBApplication $app)
    {
        $thumbnailfile = $layout->getPicPath();
        if (empty($thumbnail)) {
            return true;
        }
        File::resolveFilepath($thumbnailfile, null, array('include_path' => $app->getResourceDir()));

        while (true === is_file($thumbnailfile) && true === is_writable($thumbnailfile)) {
            @unlink($thumbnailfile);

            $thumbnailfile = $layout->getPicPath();
            File::resolveFilepath($thumbnailfile, null, array('include_path' => $app->getResourceDir()));
        }

        return true;
    }

    /**
     * Returns layout models.
     *
     * @access public
     *
     * @return array Array of Layout
     */
    public function getModels()
    {
        try {
            $q = $this->createQueryBuilder('l')
                    ->where('l._site IS NULL')
                    ->orderBy('l._label', 'ASC')
                    ->getQuery();

            return $q->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }
}
