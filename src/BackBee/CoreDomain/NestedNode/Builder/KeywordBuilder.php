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

namespace BackBee\CoreDomain\NestedNode\Builder;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class KeywordBuilder
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * KeywordBuilder's constructor.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Create new entity BackBee\CoreDomain\NestedNode\KeyWord with $keyword if not exists.
     *
     * @param string $keyword
     *
     * @return BackBee\CoreDomain\NestedNode\KeyWord
     */
    public function createKeywordIfNotExists($keyword, $do_persist = true)
    {
        if (null === $keyword_object = $this->em->getRepository('BackBee\CoreDomain\NestedNode\KeyWord')->exists($keyword)) {
            $keyword_object = new \BackBee\CoreDomain\NestedNode\KeyWord();
            $keyword_object->setRoot($this->em->find('BackBee\CoreDomain\NestedNode\KeyWord', md5('root')));
            $keyword_object->setKeyWord(preg_replace('#[/\"]#', '', trim($keyword)));

            if (true === $do_persist) {
                $this->em->persist($keyword_object);
                $this->em->flush($keyword_object);
            }
        }

        return $keyword_object;
    }
}
