<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\StandardBundle\Event\Listener;

use BackBee\CoreDomain\Renderer\Event\RendererEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Event;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Autoblock Listener
 *
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class AutoblockListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function onRender(RendererEvent $event)
    {
        $renderer = $event->getRenderer();

        $content = $renderer->getObject();
        $parentNode = self::getParentNode($content->getParamValue('parent_node'), $renderer);

        $selector = ['parentnode' => [($parentNode !== null) ? $parentNode->getUid() : null]];

        $contents = $this->entityManager->getRepository('BackBee\CoreDomain\ClassContent\AbstractClassContent')
                             ->getSelection(
                                 $selector,
                                 in_array('multipage', $content->getParamValue('multipage')),
                                 in_array('recursive', $content->getParamValue('recursive')),
                                 (int) $content->getParamValue('start'),
                                 (int) $content->getParamValue('limit'),
//                             @TODO gvf
//                                 self::$application->getBBUserToken() === null,
                                 false,
                                 false,
                                 (array) $content->getParamValue('content_to_show'),
                                 (int) $content->getParamValue('delta')
                             );

        $count = $contents instanceof Paginator ? $contents->count() : count($contents);

        $renderer->assign('contents', $contents);
        $renderer->assign('nbContents', $count);
        $renderer->assign('parentNode', $parentNode);
    }

    private function getParentNode($parentNodeParam, $renderer)
    {
        $parentNode = null;

        if (!empty($parentNodeParam)) {
            if (isset($parentNodeParam['pageUid'])) {
                $parentNode = $this->entityManager->getRepository('BackBee\CoreDomain\NestedNode\Page')->find($parentNodeParam['pageUid']);
            }
        } else {
            $parentNode = $renderer->getCurrentPage();
        }

        return $parentNode;
    }
}