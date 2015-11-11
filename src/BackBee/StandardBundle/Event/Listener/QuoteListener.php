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

/**
 * Quote Listener
 *
 * @author f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class QuoteListener
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

        $links = $content->getParamValue('link');
        $link = [
                    'url' => '',
                    'title' => 'Visit',
                    'target' => '_self'
                ];

        if (!empty($links)) {
            $links = reset($links);
            if (isset($links['pageUid']) && !empty($links['pageUid'])) {
                $page = $this->entityManager->getRepository('BackBee\CoreDomain\NestedNode\Page')->find($links['pageUid']);
                if ($page !== null) {
                    $link['url'] = $page->getUrl();
                }
            }

            if (empty($link['url']) && isset($links['url'])) {
                $link['url'] = $links['url'];
            }

            if (isset($links['title'])) {
                $link['title'] = $links['title'];
            }

            if (isset($links['target'])) {
                $link['target'] = $links['target'];
            }
        }

        $renderer->assign('link', $link);
    }
}