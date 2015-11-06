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

namespace BackBee\CoreDomain\NestedNode\Repository;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\ClassContent\Revision;

use Exception;

/**
 * Media folder repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class MediaFolderRepository extends NestedNodeRepository
{

    public function getRoot()
    {
        try {
            $q = $this->createQueryBuilder('mf')
                    ->andWhere('mf._parent is null')
                    ->getQuery();

            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    public function getMediaFolders($parent, $orderInfos, $paging = array())
    {
        $qb = $this->createQueryBuilder("mf");
        $qb->andParentIs($parent);

        /* order */
        if(is_array($orderInfos)) {
            if (array_key_exists("field", $orderInfos) && array_key_exists("dir", $orderInfos)) {
                 $qb->orderBy("mf.".$orderInfos["field"], $orderInfos["dir"]);
            }
        }
        /* paging */
        if (is_array($paging) && !empty($paging)) {
           if (array_key_exists("start", $paging) && array_key_exists("limit", $paging)) {
               $qb->setFirstResult($paging["start"])
                       ->setMaxResults($paging["limit"]);
               $result = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
           }
       } else {
           $result = $qb->getQuery()->getResult();
       }
       return $result;
    }
}
