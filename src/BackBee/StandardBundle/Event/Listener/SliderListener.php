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
 * Slider Listener
 *
 * @author f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class SliderListener
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onRender(RendererEvent $event)
    {
        $renderer = $event->getRenderer();

        $content = $renderer->getObject();
        $mediaRepository = $this->entityManager->getRepository('BackBee\CoreDomain\ClassContent\Media\Image');

        $mediasParam = $content->getParamValue('medias');
        $linksParam = $content->getParamValue('links');

        $slides = [];
        $i = 0;
        foreach ($mediasParam as $mediaParam) {
            if (isset($mediaParam['uid'])) {
                $media = $mediaRepository->find($mediaParam['uid']);
                if (null !== $media) {
                    $slides[$i] = [];
                    $slides[$i]['media'] = $media;

                    if (isset($linksParam[$i])) {
                        $slides[$i]['link'] = self::getLink($linksParam[$i]);
                    }
                }
            }

            $i++;
        }

        $renderer->assign('slides', $slides);
    }

    private function getLink($linkParam)
    {
        $link = [
            'url'    => '',
            'title'  => 'Visit',
            'target' => '_self',
        ];

        if (isset($linkParam['pageUid']) && !empty($linkParam['pageUid'])) {
            $page = $this->entityManager->getRepository('BackBee\CoreDomain\NestedNode\Page')->find($linkParam['pageUid']);
            if (null !== $page) {
                $link['url'] = $page->getUrl();
            }
        }

        if (empty($link['url']) && isset($linkParam['url'])) {
            $link['url'] = $linkParam['url'];
        }

        if (isset($linkParam['title'])) {
            $link['title'] = $linkParam['title'];
        }

        if (isset($linkParam['target'])) {
            $link['target'] = $linkParam['target'];
        }

        return $link;
    }
}