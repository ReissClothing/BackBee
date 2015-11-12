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
use BackBee\CoreDomainBundle\Controller\Exception\FrontControllerException;
use BackBee\Util\MimeType;
use BackBee\Utils\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * MediaController provide actions to BackBee medias routes (get and upload).
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 * @author      Gonzalo Vilaseca <gvf.vilaseca@gmail.com>
 */
class MediaController extends Controller
{
//    /**
//     * Handles a media file request.
//     *
//     * @param string $filename The media file to provide
//     *
//     * @throws FrontControllerException
//     *
//     * @return Response
//     */
//    public function mediaAction(Request $request, $type, $filename, $includePath = array())
//    {
//        $includePath = array_merge(
//            $includePath,
//            array($this->container->getParameter('bbapp.storage.dir'), $this->container->getParameter('bbapp.media.dir'))
//        );
//
//        if (null !== $this->getUser()) {
//            $includePath[] = $this->container->getParameter('bbapp.temporary.dir');
//        }
//
//        $matches = array();
//        if (preg_match('/([a-f0-9]{3})\/([a-f0-9]{29})\/(.*)\.([^\.]+)/', $filename, $matches)) {
//            $filename = $matches[1] . '/' . $matches[2] . '.' . $matches[4];
//        } elseif (preg_match('/([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})\/.*\.([^\.]+)/', $filename, $matches)) {
//            $filename = $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5] . $matches[6] . $matches[7] . $matches[8] . '.' . $matches[9];
//            File::resolveMediapath($filename, null, array('include_path' => $includePath));
//        }
//
//        File::resolveFilepath($filename, null, array('include_path' => $includePath));
//        $this->container->get('logger')->info(sprintf('Handling image URL `%s`.', $filename));
//
//        if (false === file_exists($filename) || false === is_readable($filename)) {
//            throw new HttpException(404, sprintf(
//                    'The file `%s` can not be found (referer: %s).',
//                    $request->getHost() . '/' . $request->getPathInfo(),
//                    $request->server->get('HTTP_REFERER')
//                )
//            );
//        }
//
//        return $this->createMediaResponse($filename);
//    }
//
//    /**
//     * Create Response object for media.
//     *
//     * @param string $filename valid filepath (file exists and readable)
//     *
//     * @return Response
//     */
//    private function createMediaResponse($filename)
//    {
//        $response = new Response();
//
//        $filestats = stat($filename);
//
//        $response->headers->set('Content-Type', MimeType::getInstance()->guess($filename));
//        $response->headers->set('Content-Length', $filestats['size']);
//
//        $response->setCache(array(
//            'etag'          => basename($filename),
//            'last_modified' => new \DateTime('@' . $filestats['mtime']),
//            'public'        => 'public',
//        ));
//
//        $response->setContent(file_get_contents($filename));
//
//        return $response;
//    }
}
