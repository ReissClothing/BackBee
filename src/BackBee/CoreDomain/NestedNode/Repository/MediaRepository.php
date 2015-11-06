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

use BackBee\NestedNode\Media;
use BackBee\NestedNode\MediaFolder;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Media repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class MediaRepository extends EntityRepository
{
    public function getMedias(MediaFolder $mediafolder, $cond, $order_sort = '_title', $order_dir = 'asc', $paging = [])
    {
        $result = null;
        $q = $this->createQueryBuilder('m')
            ->leftJoin('m._media_folder', 'mf')
            ->leftJoin('m._content', 'mc')
            ->where('mf._root = :root')
            ->andWhere('mf._leftnode >= :leftnode')
            ->andWhere('mf._rightnode <= :rightnode')
            ->orderBy('m.'.$order_sort, $order_dir)
            ->setParameters([
                'root'      => $mediafolder->getRoot(),
                'leftnode'  => $mediafolder->getLeftnode(),
                'rightnode' => $mediafolder->getRightnode(),
            ])
        ;

        $typeField = isset($cond['typeField']) && 'all' !== $cond['typeField'] ? $cond['typeField'] : null;
        if (null !== $typeField) {
            $q->andWhere('mc INSTANCE OF '.$typeField);
        }

        $searchField = isset($cond['searchField']) ? $cond['searchField'] : null;
        if (null !== $searchField) {
            $q->andWhere($q->expr()->like('mc._label', $q->expr()->literal('%'.$searchField.'%')));
        }

        $title = isset($cond['mediaTitle']) ? $cond['mediaTitle'] : null;
        if (null !== $title) {
            $q->andWhere($q->expr()->like('m._title', $q->expr()->literal('%'.$title.'%')));
        }

        $afterPubdateField = isset($cond['afterPubdateField']) && !empty($cond['afterPubdateField'])
            ? $cond['afterPubdateField']
            : null
        ;
        if (null !== $afterPubdateField) {
            $q
                ->andWhere('mc._modified > :afterPubdateField')
                ->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField))
            ;
        }

        $beforePubdateField = isset($cond['beforePubdateField']) && !empty($cond['beforePubdateField'])
            ? $cond['beforePubdateField']
            : null
        ;
        if (null !== $beforePubdateField) {
            $q
                ->andWhere('mc._modified < :beforePubdateField')
                ->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField))
            ;
        }

        if (is_array($paging)) {
            if (array_key_exists('start', $paging) && array_key_exists('limit', $paging)) {
                $q
                    ->setFirstResult($paging['start'])
                    ->setMaxResults($paging['limit'])
                ;
                $result = new Paginator($q);
            }
        } else {
            $result = $q->getQuery()->getResult();
        }

        return $result;
    }

    public function countMedias(MediaFolder $mediafolder, $cond = [])
    {
        $q = $this->createQueryBuilder('m')
            ->select('COUNT(m)')
            ->leftJoin('m._media_folder', 'mf')
            ->leftJoin('m._content', 'mc')
            ->where('mf._root = :root')
            ->andWhere('mf._leftnode >= :leftnode')
            ->andWhere('mf._rightnode <= :rightnode')
            ->setParameters([
                'root'      => $mediafolder->getRoot(),
                'leftnode'  => $mediafolder->getLeftnode(),
                'rightnode' => $mediafolder->getRightnode(),
            ])
        ;

        $typeField = isset($cond['typeField']) && 'all' !== $cond['typeField'] ? $cond['typeField'] : null;
        if (null !== $typeField) {
            $q->andWhere('mc INSTANCE OF '.$typeField);
        }

        $searchField = isset($cond['searchField']) ? $cond['searchField'] : null;
        if (null !== $searchField) {
            $q->andWhere($q->expr()->like('mc._label', $q->expr()->literal('%'.$searchField.'%')));
        }

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : null;
        if (null !== $afterPubdateField) {
            $q
                ->andWhere('mc._modified > :afterPubdateField')
                ->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField))
            ;
        }

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : null;
        if (null !== $beforePubdateField) {
            $q
                ->andWhere('mc._modified < :beforePubdateField')
                ->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField))
            ;
        }

        return $q->getQuery()->getSingleScalarResult();
    }

    public function getMediasByFolder(MediaFolder $mediafolder)
    {
        $q = $this->createQueryBuilder('m')
            ->leftJoin('m._media_folder', 'mf')
            ->leftJoin('m._content', 'mc')
            ->andWhere('mf._root = :root')
            ->andWhere('mf._leftnode >= :leftnode')
            ->andWhere('mf._rightnode <= :rightnode')
            ->setParameters([
                'root'      => $mediafolder->getRoot(),
                'leftnode'  => $mediafolder->getLeftnode(),
                'rightnode' => $mediafolder->getRightnode(),
            ])
        ;

        return $q->getQuery()->getResult();
    }
}
