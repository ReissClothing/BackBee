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

use BackBee\ApplicationInterface;
use BackBee\CoreDomain\ClassContent\AbstractContent;
use BackBee\Routing\RouteCollection;

/**
 * Iconizer returning default class content thumbnail.
 * 
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class ThumbnailIconizer implements IconizerInterface
{
    /**
     * The container base folder of thumbnails.
     * 
     * @var string
     */
    private $baseFolder;

    /**
     * The class content thumbnails folder paths.
     * 
     * @var array
     */
    private $thumbnailBaseDir;
    /**
     * @var
     */
    private $classcontentThumbnailBaseFolder;

    /**
     * Class contructor.
     * 
     * @param ApplicationInterface $application The current application.
     */
//    public function __construct($classcontentThumbnailBaseFolder)
//    {
//        $this->classcontentThumbnailBaseFolder = $classcontentThumbnailBaseFolder;
//    }

    /**
     * Returns the URI of the icon of the provided content.
     * 
     * @param  AbstractContent $content The content.
     * 
     * @return string|null              The icon URL if found, null otherwise.
     */
    public function getIcon(AbstractContent $content)
    {
        return $content->getDefaultImageName();

        if (null === $baseFolder = $this->getBaseFolder()) {
            return null;
        }

        $defaultImage = $content->getDefaultImageName();
        if ('/' === substr($defaultImage, 0, 1)) {
            $iconUrl = $defaultImage;
        } else {
            $iconUrl = $this->resolveResourceThumbnail($defaultImage);
        }

        return $iconUrl;
    }

    /**
     * Resolves the thumbnail resource URL depending on the default image provided.
     * 
     * @param  string $defaultImage The default image of a content.
     * 
     * @return string               The resolved URL if found.
     */
    private function resolveResourceThumbnail($defaultImage)
    {
        $baseFolder = $this->getBaseFolder();
        $iconUrl = $baseFolder.DIRECTORY_SEPARATOR.'default_thumbnail.png';
        foreach ($this->getThumbnailBaseFolderPaths() as $path) {
            $imageFilePath = $path.DIRECTORY_SEPARATOR.$defaultImage;

            if (file_exists($imageFilePath) && is_readable($imageFilePath)) {
                $iconUrl = $baseFolder.DIRECTORY_SEPARATOR.$defaultImage;
                break;
            }
        }

        return $iconUrl;
    }

    /**
     * Returns the container base folder for thumnails or null 
     * 
     * @return string|null
     */
    private function getBaseFolder()
    {
//        $container = $this->application->getContainer();
//        if (
//                null === $this->baseFolder
//                && $container->hasParameter('classcontent_thumbnail.base_folder')
//        ) {
//            $this->baseFolder = $container->getParameter('classcontent_thumbnail.base_folder');
//        }

        return $this->classcontentThumbnailBaseFolder;
    }

    /**
     * Getter of class content thumbnails folder paths
     *
     * @return array The class content thumbnails flder path if found, null otherwise.
     */
    private function getThumbnailBaseFolderPaths()
    {
        if (is_array($this->thumbnailBaseDir)) {
            return $this->thumbnailBaseDir;
        }

        $this->thumbnailBaseDir = [];

        if (null !== $baseFolder = $this->getBaseFolder()) {
            $thumbnailBaseDir = array_map(function ($directory) use ($baseFolder) {
                return str_replace(
                    DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    $directory.DIRECTORY_SEPARATOR.$baseFolder
                );
            }, $this->application->getResourceDir());

            foreach (array_unique($thumbnailBaseDir) as $directory) {
                if (is_dir($directory)) {
                    $this->thumbnailBaseDir[] = $directory;
                }
            }
        }

        return $this->thumbnailBaseDir;
    }
}
