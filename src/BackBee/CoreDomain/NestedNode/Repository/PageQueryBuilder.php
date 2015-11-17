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

use BackBee\CoreDomain\Site\Layout;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\Site\Site;

/**
 * This class is responsible for building DQL query strings for Page.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageQueryBuilder extends QueryBuilder
{
    /**
     * The root alias of this query
     * @var string
     */
    private $alias;

    /**
     * The join alias to section of this query
     * @var string
     */
    private $sectionAlias;

    /**
     * Joined field of section
     * @var array
     */
    private static $joinCriteria = array(
        '_root',
        '_parent',
        '_leftnode',
        '_rightnode',
        '_site',
    );

    /**
     * Options
     * @var array
     */
    public static $config = array(
        // date scheme to use in order to test publishing and archiving, should be Y-m-d H:i:00 for get 1 minute query cache
        'dateSchemeForPublishing' => 'Y-m-d H:i:00',
    );

    /**
     * Are some criteria joined fields of section?
     *
     * @param  array|null           $criteria           Optional, the criteria to test
     *
     * @return boolean                                  True if some criteria are joined fields of section, false elsewhere
     */
    public static function hasJoinCriteria(array $criteria = null)
    {
        if (null === $criteria) {
            return false;
        }

        return (0 < count(array_intersect(self::$joinCriteria, array_keys($criteria))));
    }

    /**
     * Add query part to select page by site.
     *
     * @param  Sie                 $site               The owning site
     *
     * @return PageQueryBuilder
     */
    public function andSiteIs(Site $site)
    {
        $suffix = $this->getSuffix();

        return $this->andWhere($this->getSectionAlias().'._site = :site'.$suffix)
                        ->setParameter('site'.$suffix, $site);
    }

    /**
     * Add query part to select on section page.
     *
     * @return PageQueryBuilder
     */
    public function andIsSection()
    {
        return $this->andWhere($this->getAlias().'._section = '.$this->getAlias());
    }

    /**
     * Add query part to select on not-section page.
     *
     * @return PageQueryBuilder
     */
    public function andIsNotSection()
    {
        return $this->andWhere($this->getAlias().'._section != '.$this->getAlias());
    }

    /**
     * Add query part to select online pages.
     *
     * @return PageQueryBuilder
     */
    public function andIsOnline()
    {
        return $this->andWhere($this->getAlias().'._state IN ('.$this->expr()->literal(Page::STATE_ONLINE).','.$this->expr()->literal(Page::STATE_ONLINE + Page::STATE_HIDDEN).')')
                        ->andWhere($this->getAlias().'._publishing IS NULL OR '.$this->getAlias().'._publishing <= '.$this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())))
                        ->andWhere($this->getAlias().'._archiving IS NULL OR '.$this->getAlias().'._archiving > '.$this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())));
    }

    /**
     * Add query part to select specific layouts pages.
     *
     * @param \BackBee\CoreDomain\Site\Layout $layout The layout to look pages for
     * @return PageQueryBuilder
     */
    public function andLayoutIs(Layout $layout)
    {
        return $this->andWhere($this->getAlias().'._layout = :layout')->setParameter('layout',  $layout);
    }

    /**
     * Add query part to select not deleted pages.
     *
     * @return PageQueryBuilder
     */
    public function andIsNotDeleted()
    {
        return $this->andWhere($this->getAlias().'._state < '.$this->expr()->literal(Page::STATE_DELETED));
    }

    /**
     * Add query part to select visible (ie online and not hidden) pages.
     *
     * @return PageQueryBuilder
     */
    public function andIsVisible()
    {
        return $this->andWhere($this->getAlias().'._state = '.$this->expr()->literal(Page::STATE_ONLINE))
                        ->andWhere($this->getAlias().'._publishing IS NULL OR '.$this->getAlias().'._publishing <= '.$this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())))
                        ->andWhere($this->getAlias().'._archiving IS NULL OR '.$this->getAlias().'._archiving > '.$this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())));
    }

    /**
     * Add query part to select ancestors of $page.
     *
     * @param  Page                 $page
     * @param  boolean              $strict             Optional, if false (by default) $node is included to the selection
     * @param  integer|null         $atLevel            Optional, filter ancestors by their level
     *
     * @return PageQueryBuilder
     */
    public function andIsAncestorOf(Page $page, $strict = false, $atLevel = null)
    {
        $suffix = $this->getSuffix();
        $this->andIsSection()
                ->andWhere($this->getSectionAlias().'._root = :root'.$suffix)
                ->andWhere($this->getSectionAlias().'._leftnode <= :leftnode'.$suffix)
                ->andWhere($this->getSectionAlias().'._rightnode >= :rightnode'.$suffix)
                ->setParameter('root'.$suffix, $page->getSection()->getRoot())
                ->setParameter('leftnode'.$suffix, $page->getSection()->getLeftnode() - (true === $page->hasMainSection() && $strict ? 1 : 0))
                ->setParameter('rightnode'.$suffix, $page->getSection()->getRightnode() + (true === $page->hasMainSection() && $strict ? 1 : 0));

        if (null !== $atLevel) {
            $this->andWhere($this->getSectionAlias().'._level = :level'.$suffix)
                    ->setParameter('level'.$suffix, $atLevel);
        }

        return $this;
    }

    /**
     * Add query part to select pages from their tree root.
     *
     * @param  Page                 $root               The root page to check
     *
     * @return PageQueryBuilder
     *
     * @throws \InvalidArgumentException                Raises if $root is not a tree root
     */
    public function andRootIs(Page $root)
    {
        if (!$root->isRoot()) {
            throw new \InvalidArgumentException('Provided page is not a tree root.');
        }

        $suffix = $this->getSuffix();
        return $this->andWhere($this->getSectionAlias().'._root = :root'.$suffix)
                        ->setParameter('root'.$suffix, $root->getSection());
    }

    /**
     * Add query part to select a specific subbranch of tree.
     *
     * @param  Page|null            $page               The parent page to check or null to select roots
     *
     * @return PageQueryBuilder
     */
    public function andParentIs(Page $page = null)
    {
        if (null === $page) {
            // Looking for root
            return $this->andWhere($this->getSectionAlias() . '._parent IS NULL')
                            ->andWhere($this->getSectionAlias() . ' = ' . $this->getAlias());
        }

        if (false === $page->hasMainSection()) {
            // Page is leaf, no child
            return $this->andWhere('1 = 0');
        }

        $suffix = $this->getSuffix();
        $qOr = $this->expr()->orX();
        $qOr->add($this->getSectionAlias().'._parent = :parent'.$suffix)
                ->add($this->getAlias().'._section = :parent'.$suffix);

        return $this->andWhere($qOr)
                        ->andWhere($this->getAlias().'._level = :level'.$suffix)
                        ->setParameter('parent'.$suffix, $page->getSection())
                        ->setParameter('level'.$suffix, $page->getLevel() + 1);
    }

    /**
     * Add query part to select siblings of page.
     *
     * @param  Page                 $page               The page to look for siblings
     * @param  boolean              $strict             Optional, if false (by default) $node is include to the selection
     * @param  array|null           $order              Optional, ordering spec ( [$field => $sort] )
     * @param  integer|null         $limit              Optional, max number of results, if null no limit
     * @param  integer              $start              Optional, first result index (0 by default)
     *
     * @return PageQueryBuilder
     */
    public function andIsSiblingsOf(Page $page, $strict = false, array $order = null, $limit = null, $start = 0)
    {
        if (null === $page->getParent()) {
            $this->andParentIs(null);
        } else {
            $this->andIsDescendantOf($page->getParent(), false, $page->getLevel());
        }

        if (true === $strict) {
            $suffix = $this->getSuffix();
            $this->andWhere($this->getAlias().' != :page'.$suffix)
                    ->setParameter('page'.$suffix, $page);
        }

        if (null !== $order) {
            $this->addMultipleOrderBy($order);
        }

        if (null !== $limit) {
            $this->setMaxResults($limit)
                    ->setFirstResult($start);
        }

        return $this;
    }

    /**
     * Add query part to select descendants of $page.
     *
     * @param  Page                 $page               The page to look for descendants
     * @param  boolean              $strict             Optional, if false (by default) $node is include to the selection
     * @param  integer|null         $depth              Optional, filter ancestors by their level
     * @param  string[]|null        $order              Optional, ordering spec ( [$field => $sort] )
     * @param  integer|null         $limit              Optional, max number of results, if null no limit
     * @param  integer              $start              Optional, first result index (0 by default)
     * @param  boolean              $limitToSection     Optional, if true limits to descendants being section
     *
     * @return PageQueryBuilder
     */
    public function andIsDescendantOf(Page $page, $strict = false, $depth = null, array $order = null, $limit = null, $start = 0, $limitToSection = false)
    {
        $suffix = $this->getSuffix();
        $this->andWhere($this->getSectionAlias().'._root = :root'.$suffix)
                ->andWhere($this->expr()->between($this->getSectionAlias().'._leftnode', $page->getSection()->getLeftnode(), $page->getSection()->getRightnode()))
                ->setParameter('root'.$suffix, $page->getSection()->getRoot());

        if (true === $strict) {
            $this->andWhere($this->getAlias().' != :page'.$suffix)
                    ->setParameter('page'.$suffix, $page);
        }

        if (null !== $depth) {
            $this->andWhere($this->getAlias().'._level <= :level'.$suffix)
                    ->setParameter('level'.$suffix, $page->getLevel() + $depth);
        }

        if (null !== $order) {
            $this->addMultipleOrderBy($order);
        }

        if (null !== $limit) {
            $this->setMaxResults($limit)
                    ->setFirstResult($start);
        }

        if (true === $limitToSection) {
            $this->andIsSection();
        }

        return $this;
    }

    /**
     * Add query part to select page having specific states.
     *
     * @param  integer|integer[]    $states             One or several states to test.
     *
     * @return PageQueryBuilder
     */
    public function andStateIsIn($states)
    {
        $suffix = $this->getSuffix();
        return $this->andWhere($this->getAlias().'._state IN (:states'.$suffix.')')
                        ->setParameter('states'.$suffix, (array) $states);
    }

    /**
     * Add query part to select page having not specific states.
     *
     * @param  integer|integer[]    $states             One or several states to test.
     *
     * @return PageQueryBuilder
     */
    public function andStateIsNotIn($states)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        $suffix = $this->getSuffix();

        return $this->andWhere($this->getAlias().'._state NOT IN (:states'.$suffix.')')
                        ->setParameter('states'.$suffix, $states);
    }

    /**
     * Add query part to select page matching provided criteria.
     *
     * @param  integer|integer[]    $restrictedStates   Optional, limit to pages having provided states, empty by default.
     * @param  string[]             $options            Optional, the search criteria:
     *                                                      * 'beforePubdateField'  => timestamp against page._modified,
     *                                                      * 'afterPubdateField'   => timestamp against page._modified,
     *                                                      * 'searchField'         => string to search for title
     *
     * @return PageQueryBuilder
     */
    public function andSearchCriteria($restrictedStates = array(), $options = array())
    {
        if (true === is_array($restrictedStates) && 0 < count($restrictedStates) && false === in_array('all', $restrictedStates)) {
            $this->andStateIsIn($restrictedStates);
        }

        if (false === is_array($options)) {
            $options = array();
        }

        if (true === array_key_exists('beforePubdateField', $options)) {
            $date = new \DateTime();
            $suffix = $this->getSuffix();
            $this->andWhere($this->getAlias().'._modified < :date'.$suffix)
                    ->setParameter('date'.$suffix, $date->setTimestamp($options['beforePubdateField']));
        }

        if (true === array_key_exists('afterPubdateField', $options)) {
            $date = new \DateTime();
            $suffix = $this->getSuffix();
            $this->andWhere($this->getAlias().'._modified > :date'.$suffix)
                    ->setParameter('date'.$suffix, $date->setTimestamp($options['afterPubdateField']));
        }

        if (true === array_key_exists('searchField', $options)) {
            $this->andWhere($this->expr()->like($this->getAlias().'._title', $this->expr()->literal('%'.$options['searchField'].'%')));
        }

        return $this;
    }

    /**
     * Add query part to math provided criteria.
     *
     * @param  array                $criteria           Array of criteria to look for( ['field' => 'value'] )
     *
     * @return PageQueryBuilder
     */
    public function addSearchCriteria(array $criteria)
    {
        $suffix = $this->getSuffix();
        foreach ($criteria as $crit => $value) {
            if (false === strpos($crit, '.')) {
                $crit = (true === in_array($crit, self::$joinCriteria) ? $this->getSectionAlias() : $this->getAlias()).'.'.$crit;
            }

            $param = str_replace('.', '_', $crit).$suffix;
            $this->andWhere($crit.' IN (:'.$param.')')
                    ->setParameter($param, $value);
        }

        return $this;
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param  string|Expr\OrderBy  $sort               The ordering expression.
     * @param  string|null          $order              The ordering direction.
     *
     * @return PageQueryBuilder
     */
    public function addOrderBy($sort, $order = null)
    {
        if (false !== strpos($sort, '.')) {
            return parent::addOrderBy($sort, $order);
        }

        if (true === in_array($sort, self::$joinCriteria)) {
            $sort = $this->getSectionAlias().'.'.$sort;
        } elseif (0 !== strpos($this->getAlias().'.', $sort)) {
            $sort = $this->getAlias().'.'.$sort;
        }

        return parent::addOrderBy($sort, $order);
    }

    /**
     * Add several ordering criteria by array.
     *
     * @param  array                $criteria           Optional, the ordering criteria ( ['_leftnode' => 'asc'] by default )
     *
     * @return PageQueryBuilder
     */
    public function addMultipleOrderBy(array $criteria = array('_position' => 'ASC'))
    {
        if (true === empty($criteria)) {
            $criteria = array('_position' => 'ASC');
        }

        foreach ($criteria as $sort => $order) {
            $this->addOrderBy($sort, $order);
        }

        return $this;
    }

    /**
     * Add multiple ordering criteria.
     *
     * @param  array                $order              Optional, the ordering criteria ( ['_position' => 'ASC', '_leftnode' => 'ASC'] by default )
     *
     * @return PageQueryBuilder
     */
    public function orderByMultiple($order = ['_position' => 'ASC', '_leftnode' => 'ASC'])
    {
        if (true === empty($order)) {
            $order = ['_position' => 'ASC', '_leftnode' => 'ASC'];
        }

        $this->resetDQLPart('orderBy');
        foreach ($order as $field => $sort) {
            $this->addOrderBy($field, $sort);
        }

        return $this;
    }

    /**
     * Try to retreive the root alias for this builder.
     *
     * @return string
     *
     * @throws BBException
     */
    public function getAlias()
    {
        if (null === $this->alias) {
            $aliases = $this->getRootAliases();
            if (0 === count($aliases)) {
                throw new BBException('Cannot access to root alias');
            }

            $this->alias = $aliases[0];
        }

        return $this->alias;

    }

    /**
     * Returns the join alias to section.
     *
     * @return string
     */
    public function getSectionAlias()
    {
        if (null === $this->sectionAlias) {
            $this->sectionAlias = $this->getAlias().'_s';
            $this->join($this->getAlias().'._section', $this->sectionAlias);
        }

        return $this->sectionAlias;
    }

    /**
     * Return new suffix for parameters.
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function getSuffix()
    {
        return ''.count($this->getParameters());
    }
}
