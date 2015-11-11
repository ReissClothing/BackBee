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

namespace BackBee\CoreDomainBundle\ClassContent\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\Site\Site;

/**
 * AbstractClassContent repository
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ClassContentQueryBuilder extends QueryBuilder
{
    /**
     * @var EntityManager
     */
    private $_em;

    /**
     * @var array
     */
    private $classmap = array(
        'IdxSiteContent' => 'BackBee\ClassContent\Indexes\IdxSiteContent',
        'AbstractClassContent' => 'BackBee\CoreDomain\ClassContent\AbstractClassContent',
    );

    /**
     * ClassContentQueryBuilder constructor.
     *
     * @param EntityManager            $em
     * @param \Doctrine\ORM\Query\Expr $select Use cc as identifier
     */
    public function __construct(EntityManager $em, Func $select = null)
    {
        $this->_em = $em;
        parent::__construct($em);
        $select = is_null($select) ? 'cc' : $select;
        $this->select($select)->distinct()->from($this->getClass('AbstractClassContent'), 'cc');
    }

    /**
     * Add site filter to the query.
     *
     * @param  mixed $site (BackBee/Site/Site|String)
     */
    public function addSiteFilter($site)
    {
        if ($site instanceof Site) {
            $site = $site->getUid();
        }

        if (!empty($site)) {
            $this->andWhere(
                'cc._uid IN (SELECT i.content_uid FROM BackBee\ClassContent\Indexes\IdxSiteContent i WHERE i.site_uid = :site_uid)'
            )->setParameter('site_uid', $site);
        }
    }

    /**
     * Set contents uid as filter.
     *
     * @param  array $uids
     */
    public function addUidsFilter(array $uids)
    {
        $this->andWhere('cc._uid in(:uids)')->setParameter('uids', $uids);
    }

    /**
     * Add limite to onlinne filter.
     */
    public function limitToOnline()
    {
        $this->leftJoin('cc._mainnode', 'mp');
        $this->andWhere('mp._state IN (:states)')
             ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
        $this->andWhere('mp._publishing < :today OR mp._publishing IS NULL')
             ->setParameter('today', new \DateTime());
    }

    /**
     * Set a page to filter the query on a nested portion.
     *
     * @param  BackBee\CoreDomain\NestedNode\Page $page
     */
    public function addPageFilter(Page $page)
    {
        if ($page && !$page->isRoot()) {
            $this->leftJoin('cc._mainnode', 'p')
                    ->leftJoin('p._section', 'sp')
                    ->andWhere('sp._root = :selectedPageRoot')
                    ->andWhere('sp._leftnode >= :selectedPageLeftnode')
                    ->andWhere('sp._rightnode <= :selectedPageRightnode')
                    ->setParameters([
                        'selectedPageRoot' => $page->getSection()->getRoot(),
                        'selectedPageLeftnode' => $page->getLeftnode(),
                        'selectedPageRightnode' => $page->getRightnode(),
                    ]);
        }
    }

    /**
     * Filter the query by keywords.
     *
     * @param  array $keywords
     */
    public function addKeywordsFilter($keywords)
    {
        $contentIds = $this->_em->getRepository('BackBee\CoreDomain\NestedNode\KeyWord')
                                ->getContentsIdByKeyWords($keywords);
        if (is_array($contentIds) && !empty($contentIds)) {
            $this->andWhere('cc._uid in(:keywords)')->setParameter('keywords', $contentIds);
        }
    }

    /**
     * Filter by rhe classname descriminator.
     *
     * @param  array $classes
     */
    public function addClassFilter($classes)
    {
        if (is_array($classes) && count($classes) !== 0) {
            $filters = array();
            foreach ($classes as $class) {
                $filters[] = 'cc INSTANCE OF \''.  $class .'\'';
            }
            $filter = implode(' OR ', $filters);

            $this->andWhere($filter);
        }
    }

    /**
     * Order with the indexation table.
     *
     * @param  string $label
     * @param  string $sort  ('ASC'|'DESC')
     */
    public function orderByIndex($label, $sort = 'ASC')
    {
        $this->join('cc._indexation', 'idx')
             ->andWhere('idx._field = :sort')
             ->setParameter('sort', $label)
             ->orderBy('idx._value', $sort);
    }

    /**
     * Get Results paginated.
     *
     * @param  integer $start
     * @param  integer $limit
     *
     * @return Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function paginate($start, $limit)
    {
        $this->setFirstResult($start)
             ->setMaxResults($limit);

        return new Paginator($this);
    }

    /**
     * Adds filter on title
     * @param string $expression
     */
    public function addTitleLike($expression)
    {
        if (null !== $expression) {
            $this->andWhere(
                $this->expr()->like(
                    'cc._label',
                    $this->expr()->literal('%'.$expression.'%')
                )
            );
        }
    }

    /**
     * Returns the classname for $ey
     *
     * @param string $key
     *
     * @return string
     */
    private function getClass($key)
    {
        if (array_key_exists($key, $this->classmap)) {
            return $this->classmap[$key];
        }
    }
}
