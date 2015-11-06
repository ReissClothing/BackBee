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

use Doctrine\ORM\EntityRepository;
use Exception;

use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\NestedNode\PageRevision;

/**
 * Page revision repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class PageRevisionRepository extends EntityRepository
{
    public function getCurrent(Page $page)
    {
        try {
            $q = $this->createQueryBuilder('r')
                    ->andWhere('r._page = :page')
                    ->andWhere('r._version = :version')
                    ->orderBy('r._id', 'DESC')
                    ->setParameters(array(
                        'page' => $page,
                        'version' => PageRevision::VERSION_CURRENT,
                    ))
                    ->getQuery();

            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }
}
