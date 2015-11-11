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
use Symfony\Component\EventDispatcher\Event;

/**
 * Article Listener
 *
 * @author f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class ArticleListener
{
    public function onRender(RendererEvent $event)
    {
        $renderer = $event->getRenderer();

        $content = $renderer->getObject();
        $tag = null;
        $url = '#';
        $mainNode = $content->getMainNode();

        if ($mainNode !== null) {
            $url = $mainNode->getUrl();
            $parentNode = $mainNode->getParent();
            if (null !== $parentNode) {
                $altTitle = $parentNode->getAltTitle();
                $tag = (!empty($altTitle)) ? $altTitle : $parentNode->getTitle();
            }
        }

        $renderer->assign('tag', $tag);
        $renderer->assign('url', $url);
    }
}