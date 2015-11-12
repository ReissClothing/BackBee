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

namespace BackBee\CoreDomainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackBee\Util\MimeType;
use BackBee\Utils\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * ResourceController expose action for BackBee resource routes.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 * @author      Gonzalo Vilaseca <gvf.vilaseca@gmail.com>
 */
class ResourceController extends Controller
{
    /**
     * Hdandles classcontent thumbnail request, returns the right thumbnail if it exists, else the default one.
     *
     * @param string $filename
     *
     * @throws HttpException
     *
     * @return Response
     */
    public function getClassContentThumbnailAction(Request $request, $filename)
    {
        $base_folder      = $this->container->getParameter('classcontent_thumbnail.base_folder');
        $base_directories = array_map(function ($directory) use ($base_folder) {
            return str_replace(
                DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                $directory . '/' . $base_folder
            );
        }, $this->container->get('bb.context.resource_dir')->getResourceDir());

        File::resolveFilepath($filename, null, array('include_path' => $base_directories));

        if (false === file_exists($filename)) {
            $filename = $this->getDefaultClassContentThumbnailFilepath($base_directories);
        }

        if (false === file_exists($filename) || false === is_readable($filename)) {

            throw new HttpException(404, sprintf(
                'The file `%s` can not be found (referer: %s).',
                $request->getHost() . '/' . $request->getPathInfo(),
                $request->server->get('HTTP_REFERER')
            ));
        }

        return $this->createResourceResponse($filename);
    }

    /**
     * Returns the default classcontent thumbnail filepath.
     *
     * @param array $base_directories list of every resources directories of current application
     *
     * @return string
     */
    private function getDefaultClassContentThumbnailFilepath(array $base_directories)
    {
        $filename = 'default_thumbnail.png';
        File::resolveFilepath($filename, null, array('include_path' => $base_directories));

        return $filename;
    }

    /**
     * Create Response object for resource.
     *
     * @param string $filename valid filepath (file exists and readable)
     *
     * @return Response
     */
    private function createResourceResponse($filename)
    {
        $response = new Response();

        $filestats = stat($filename);

        $response->headers->set('Content-Type', MimeType::getInstance()->guess($filename));
        $response->headers->set('Content-Length', $filestats['size']);

        $response->setCache(array(
            'etag'          => basename($filename),
            'last_modified' => new \DateTime('@' . $filestats['mtime']),
            'public'        => 'public',
        ));

        $response->setContent(file_get_contents($filename));

        return $response;
    }
}
