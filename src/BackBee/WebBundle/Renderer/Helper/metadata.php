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

namespace BackBee\WebBundle\Renderer\Helper;

use BackBee\MetaData\MetaDataBag;

/**
 * Helper generating <META> tag for the page being rendered
 * if none available, the default metadata are generaed.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class metadata extends AbstractHelper
{
    public function __invoke()
    {
        if (null === $renderer = $this->_renderer) {
            return '';
        }

        if (null === $page = $renderer->getCurrentPage()) {
            return '';
        }

        $metadata = $page->getMetadata();

        if (null === $metadata || $metadata->count() === 0) {
            $metadata = new MetaDataBag($renderer->getApplication()->getConfig()->getMetadataConfig(), $page);
            $page->setMetaData($metadata);
            if ($renderer->getApplication()->getEntityManager()->contains($page)) {
                $renderer->getApplication()->getEntityManager()->flush($page);
            }
        }

        $result = '';
        foreach ($metadata as $meta) {
            if (0 < $meta->count() && 'title' !== $meta->getName()) {
                $result .= '<meta ';
                foreach ($meta as $attribute => $value) {
                    if (false !== strpos($meta->getName(), 'keyword') && 'content' === $attribute) {
                        $keywords = explode(',', $value);
                        foreach ($this->getKeywordObjects($keywords) as $object) {
                            $value = trim(str_replace($object->getUid(), $object->getKeyWord(), $value), ',');
                        }
                    }

                    $result .= $attribute.'="'.html_entity_decode($value, ENT_COMPAT, 'UTF-8').'" ';
                }
                $result .= '/>'.PHP_EOL;
            }
        }

        return $result;
    }

    /**
     * Returns KeyWord entities with provided array.
     *
     * @param  array  $keywords
     * @return array
     */
    private function getKeywordObjects(array $keywords)
    {
        $keywords = (array) $keywords;

        return $this->getRenderer()
            ->getEntityManager()
            ->getRepository('BackBee\CoreDomain\NestedNode\KeyWord')
            ->getKeywordsFromElements($keywords)
        ;
    }
}
