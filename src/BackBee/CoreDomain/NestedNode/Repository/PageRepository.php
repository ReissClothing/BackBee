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
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\ClassContent\ContentSet;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\NestedNode\Section;
use BackBee\Security\Token\BBUserToken;
use BackBee\CoreDomain\Site\Site;
use InvalidArgumentException;

/**
 * Page repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageRepository extends EntityRepository
{
    /**
     * Creates a new Page QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string                $alias              The alias to use.
     * @param string                $indexBy            Optional, the index to use for the query.
     *
     * @return PageQueryBuilder                         The page query builder for this repository.
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $qb = new PageQueryBuilder($this->_em);

        return $qb->select($alias)->from($this->_entityName, $alias, $indexBy);
    }

    /**
     * Finds entities by a set of criteria with automatic join on section if need due to retro-compatibility.
     *
     * @param  array                $criteria           An array of criteria ( field => value ).
     * @param  array|null           $orderBy            Optional, an array of ordering criteria ( [field => direction] ).
     * @param  integer|null         $limit              Optional, the max number of results.
     * @param  integer|null         $offset             Optional, The starting index of results.
     *
     * @return Page[]                                   An array of matching pages.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if (
                false === PageQueryBuilder::hasJoinCriteria($criteria) &&
                false === PageQueryBuilder::hasJoinCriteria($orderBy)
        ) {
            return parent::findBy($criteria, $orderBy, $limit, $offset);
        }

        $query = $this->createQueryBuilder('p')
                        ->addSearchCriteria($criteria);

        if (false === empty($orderBy)) {
            $query->addMultipleOrderBy($orderBy);
        }

        return $query->setMaxResults($limit)
                        ->setFirstResult($offset)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Finds a single entity by a set of criteria with automatic join on section if need due to retro-compatibility.
     *
     * @param  array                $criteria           An array of criteria.
     * @param  array|null           $orderBy            Optional, an array of ordering criteria.
     *
     * @return object|null                              The page instance or null if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        if (
                false === PageQueryBuilder::hasJoinCriteria($criteria) &&
                false === PageQueryBuilder::hasJoinCriteria($orderBy)
        ) {
            return parent::findOneBy($criteria, $orderBy);
        }

        $query = $this->createQueryBuilder('p')
                ->addSearchCriteria($criteria);

        if (false === empty($orderBy)) {
            $query->addMultipleOrderBy($orderBy);
        }

        return $query->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the ancestor at level $level of the provided page.
     *
     * @param  Page                 $page               The page to look for ancestor.
     * @param  integer              $level              Optional, the level of the ancestor (0 by default, ie root).
     *
     * @return Page|null                                The page instance or null if the entity can not be found.
     */
    public function getAncestor(Page $page, $level = 0)
    {
        if ($page->getLevel() < $level) {
            return;
        }

        if ($page->getLevel() === $level) {
            return $page;
        }

        return $this->createQueryBuilder('p')
                        ->andIsAncestorOf($page, false, $level)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the ancestors of the provided page.
     *
     * @param  Page                 $page               The page to look for ancestors.
     * @param  integer|null         $depth              Optional, limits results to ancestors from $depth number of generation.
     * @param  boolean              $includeNode        Optional, include the node itsef to results if true (false by default).
     *
     * @return Page[]                                   An array of matching ancestors.
     */
    public function getAncestors(Page $page, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('p')
                ->andIsAncestorOf($page, !$includeNode, null === $depth ? null : $page->getLevel() - $depth);

        $results = $q->orderBy($q->getSectionAlias().'._leftnode', 'asc')
                ->getQuery()
                ->getResult();

        if (true === $includeNode && false === $page->hasMainSection()) {
            $results[] = $page;
        }

        return $results;
    }

    /**
     * Returns the previous online sibling of $page.
     *
     * @param  Page                 $page               The page to look for previous sibling
     *
     * @return Page|null                                The previous page or null if the entity can not be found.
     */
    public function getOnlinePrevSibling(Page $page)
    {
        $query = $this->createQueryBuilder('p')
                ->andIsSiblingsOf($page, true, ['_leftnode' => 'DESC', '_position' => 'DESC'], 1, 0)
                ->andIsOnline();

        if (true === $page->hasMainSection()) {
            $query->andIsSection()
                    ->andWhere($query->getSectionAlias().'._leftnode < :leftnode')
                    ->setParameter('leftnode', $page->getLeftnode());
        } else {
            $qOR = $query->expr()->orX();
            $qOR->add('p._section != p')
                    ->add($query->getSectionAlias().'._parent = :parent');

            $query->andWhere('p._position < :position')
                    ->andWhere($qOR)
                    ->setParameter('position', $page->getPosition())
                    ->setParameter('parent', $page->getParent()->getSection());
        }

        return $query->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the online siblings having layout $layout of the provided page.
     *
     * @param  Page                 $page               The page to look for siblings having $layout.
     * @param  Layout               $layout             The layout to look for.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     * @param  array|null           $order              Optional, the ordering criteria ( [field => $sort] ).
     * @param  integer|null         $limit              Optional, the maximum number of results.
     * @param  integer              $start              Optional, the first result index (0 by default).
     *
     * @return Page[]                                   An array of matching siblings.
     */
    public function getOnlineSiblingsByLayout(Page $page, Layout $layout, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('p')
                        ->andIsSiblingsOf($page, !$includeNode, $order, $limit, $start)
                        ->andIsOnline()
                        ->andWhere('p._layout = :layout')
                        ->setParameter('layout', $layout)
                        ->andWhere('p._level = :level')
                        ->setParameter('level', $page->getLevel())
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the next online sibling of $page.
     *
     * @param  Page                 $page               The page to look for next sibling.
     *
     * @return Page|null                                The next page or null if the entity can not be found.
     */
    public function getOnlineNextSibling(Page $page)
    {
        $query = $this->createQueryBuilder('p');

        if (true === $page->hasMainSection()) {
            $query->andWhere($query->getSectionAlias().'._leftnode >= :leftnode')
                    ->orWhere('p._section IN (:sections)')
                    ->setParameter('leftnode', $page->getLeftnode())
                    ->setParameter('sections', [$page->getSection(), $page->getSection()->getParent()]);
        } else {
            $query->andWhere('p._position > :position')
                    ->setParameter('position', $page->getPosition());
        }

        return $query->andIsSiblingsOf($page, true, ['_position' => 'ASC', '_leftnode' => 'ASC'], 1, 0)
                        ->andIsOnline()
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Inserts a page in a tree at first position.
     *
     * @param  Page                 $page               The page to be inserted.
     * @param  Page                 $parent             The parent node.
     * @param  boolean              $section            If true, the page is inserted with a section (false by default).
     *
     * @return Page                                     The inserted page.
     */
    public function insertNodeAsFirstChildOf(Page $page, Page $parent, $section = false)
    {
        return $this->insertNode($page, $parent, 1, $section);
    }

    /**
     * Inserts a page in a tree at last position.
     *
     * @param  Page                 $page               The page to be inserted.
     * @param  Page                 $parent             The parent node.
     * @param  boolean              $section            If true, the page is inserted with a section (false by default).
     *
     * @return Page                                     The inserted page.
     */
    public function insertNodeAsLastChildOf(Page $page, Page $parent, $section = false)
    {
        return $this->insertNode($page, $parent, $this->getMaxPosition($parent) + 1, $section);
    }

    /**
     * Inserts a page in a tree.
     *
     * @param  Page                 $page               The page to be inserted.
     * @param  Page                 $parent             The parent node.
     * @param  integer              $position           The position of the inserted page
     * @param  boolean              $section            If true, the page is inserted with a section (false by default).
     *
     * @return Page                                     The inserted page.
     *
     * @throws InvalidArgumentException                 Raises if parent page is not a section.
     */
    public function insertNode(Page $page, Page $parent, $position, $section = false)
    {
        if (!$parent->hasMainSection()) {
            throw new InvalidArgumentException('Parent page is not a section.');
        }

        $current_parent = $page->getSection();
        $page->setParent($parent)
                ->setPosition($position)
                ->setLevel($parent->getSection()->getLevel() + 1);

        if (true === $section) {
            $page = $this->saveWithSection($page, $current_parent);
        } else {
            $this->shiftPosition($page, 1, true);
        }

        $parent->getSection()->setHasChildren(true);

        return $page;
    }

    /**
     * Returns default ordering criteria for descendants if none provided.
     *
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  array                $order              Optional, the ordering criteria ( [] by default ).
     *
     * @return array                                    If none ordering criteria provided and only one descendant generation is requested
     *                                                  the result will be array['_position' => 'ASC', '_leftnode' => 'ASC']
     *                                                  If none ordering criteria provided and several generations requested the result
     *                                                  will be array['_leftnode' => 'ASC', '_level' => 'ASC', '_position' => 'ASC']
     *                                                  elsewhere $order.
     */
    private function getOrderingDescendants($depth = null, $order = [])
    {
        if (1 === $depth && true === empty($order)) {
            $order = [
                '_position' => 'ASC',
                '_leftnode' => 'ASC',
            ];
        } elseif (true === empty($order)) {
            $order = [
                '_leftnode' => 'ASC',
                '_level' => 'ASC',
                '_position' => 'ASC',
            ];
        }

        return $order;
    }

    /**
     * Returns the descendants of $page.
     *
     * @param  Page                 $page               The page to look for descendants.
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     * @param  array                $order              Optional, the ordering criteria ( [] by default ).
     * @param  boolean              $paginate           Optional, if true return a paginator rather than an array (false by default).
     * @param  integer              $start              Optional, if paginated set the first result index (0 by default).
     * @param  integer              $limit              Optional, if paginated set the maxmum number of results (25 by default).
     * @param  boolean              $limitToSection     Optional, limit to descendants having child (false by default).
     * @param  string|null          $state              Optional, either 'visible', 'online', 'not-deleted' or empty.
     *
     * @return Page[]|Paginator                         The matching pages if $paginate is false, a Paginator elsewhere.
     */
    private function getDescendantsWithState(Page $page, $depth = null, $includeNode = false, $order = [], $paginate = false, $start = 0, $limit = 25, $limitToSection = false, $state = null)
    {
        if (true === $page->isLeaf()) {
            return [];
        }

        $query = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, !$includeNode, $depth, $this->getOrderingDescendants($depth, $order), (true === $paginate) ? $limit : null, $start, $limitToSection);

        switch ($state) {
            case 'onine':
                $query->andIsOnline();
                break;
            case 'visible':
                $query->andIsVisible();
                break;
            case 'not-deleted':
                $query->andIsNotDeleted();
                break;
        }

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Returns the descendants of $page.
     *
     * @param  Page                 $page               The page to look for descendants.
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     * @param  array                $order              Optional, the ordering criteria ( [] by default ).
     * @param  boolean              $paginate           Optional, if true return a paginator rather than an array (false by default).
     * @param  integer              $start              Optional, if paginated set the first result index (0 by default).
     * @param  integer              $limit              Optional, if paginated set the maxmum number of results (25 by default).
     * @param  boolean              $limitToSection     Optional, limit to descendants having child (false by default).
     *
     * @return Page[]|Paginator                         The matching pages if $paginate is false, a Paginator elsewhere.
     */
    public function getDescendants(Page $page, $depth = null, $includeNode = false, $order = [], $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        return $this->getDescendantsWithState($page, $depth, $includeNode, $order, $paginate, $start, $limit, $limitToSection);
    }

    /**
     * Returns the online descendants of $page.
     *
     * @param  Page                 $page               The page to look for descendants.
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     * @param  array                $order              Optional, the ordering criteria ( [] by default ).
     * @param  boolean              $paginate           Optional, if true return a paginator rather than an array (false by default).
     * @param  integer              $start              Optional, if paginated set the first result index (0 by default).
     * @param  integer              $limit              Optional, if paginated set the maxmum number of results (25 by default).
     * @param  boolean              $limitToSection     Optional, limit to descendants having child (false by default).
     *
     * @return Page[]|Paginator                         The matching pages if $paginate is false, a Paginator elsewhere.
     */
    public function getOnlineDescendants(Page $page, $depth = null, $includeNode = false, $order = [], $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        return $this->getDescendantsWithState($page, $depth, $includeNode, $order, $paginate, $start, $limit, $limitToSection, 'online');
    }

    /**
     * Returns the visible (ie online and not hidden) descendants of $page.
     *
     * @param  Page                 $page               The page to look for descendants.
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     * @param  array                $order              Optional, the ordering criteria ( [] by default ).
     * @param  boolean              $paginate           Optional, if true return a paginator rather than an array (false by default).
     * @param  integer              $start              Optional, if paginated set the first result index (0 by default).
     * @param  integer              $limit              Optional, if paginated set the maxmum number of results (25 by default).
     * @param  boolean              $limitToSection     Optional, limit to descendants having child (false by default).
     *
     * @return Page[]|Paginator                         The matching pages if $paginate is false, a Paginator elsewhere.
     */
    public function getVisibleDescendants(Page $page, $depth = null, $includeNode = false, $order = [], $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        return $this->getDescendantsWithState($page, $depth, $includeNode, $order, $paginate, $start, $limit, $limitToSection, 'visible');
    }

    /**
     * Returns the visible (ie online and not hidden) children of $page.
     *
     * @param  Page                 $page               The page to look for descendants.
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     *
     * @return Page[]                                   The matching pages if $paginate is false, a Paginator elsewhere.
     *
     * @deprecated
     */
    public function getVisibleDescendantsFromParent(Page $page, $depth = null, $includeNode = false)
    {
        return $this->getVisibleDescendants($page, 1, $includeNode);
    }

    /**
     * Returns the not deleted descendants of $page.
     *
     * @param  Page                 $page               The page to look for descendants.
     * @param  integer|null         $depth              Optional, limit to $depth number of generation.
     * @param  boolean              $includeNode        Optional, include $page in results if true (false by default).
     * @param  array                $order              Optional, the ordering criteria ( [] by default ).
     * @param  boolean              $paginate           Optional, if true return a paginator rather than an array (false by default).
     * @param  integer              $start              Optional, if paginated set the first result index (0 by default).
     * @param  integer              $limit              Optional, if paginated set the maxmum number of results (25 by default).
     * @param  boolean              $limitToSection     Optional, limit to descendants having child (false by default).
     *
     * @return Page[]|Paginator                         The matching pages if $paginate is false, a Paginator elsewhere.
     */
    public function getNotDeletedDescendants(Page $page, $depth = null, $includeNode = false, $order = [], $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        return $this->getDescendantsWithState($page, $depth, $includeNode, $order, $paginate, $start, $limit, $limitToSection, 'not-deleted');
    }

    /**
     * Move page as first child of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     *
     * @return Page                                     The moved page.
     */
    public function moveAsFirstChildOf(Page $page, Page $target)
    {
        return $this->moveAsChildOf($page, $target, true);
    }

    /**
     * Move page as last child of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     *
     * @return Page                                     The moved page.
     */
    public function moveAsLastChildOf(Page $page, Page $target)
    {
        return $this->moveAsChildOf($page, $target, false);
    }

    /**
     * Move page as child of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     * @param  boolean              $asFirst            Move page as first child of $target if true (default), last child elsewhere.
     *
     * @return Page                                     The moved page.
     *
     * @throws InvalidArgumentException                 Raises if target page is not a section.
     */
    private function moveAsChildOf(Page $page, Page $target, $asFirst = true)
    {
        if (false === $target->hasMainSection()) {
            throw new InvalidArgumentException('Cannot move page into a non-section page.');
        }

        if (false === $page->hasMainSection()) {
            $this->movePageAsChildOf($page, $target, $asFirst);
        } else {
            $this->moveSectionAsChildOf($page, $target, $asFirst);
        }

        $target->getSection()->setHasChildren(true);

        return $page->setParent($target)
                        ->setLevel($target->getLevel() + 1);
    }

    /**
     * Move a non-section page as child of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     * @param  boolean              $asFirst            Move page as first child of $target if true (default), last child elsewhere.
     *
     * @return Page                                     The moved page.
     */
    private function movePageAsChildOf(Page $page, Page $target, $asFirst = true)
    {
        if (true === $asFirst) {
            return $this->shiftPosition($page, -1, true)
                            ->insertNodeAsFirstChildOf($page, $target);
        } else {
            return $this->shiftPosition($page, -1, true)
                            ->insertNodeAsLastChildOf($page, $target);
        }
    }

    /**
     * Move a section page as child of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     * @param  boolean              $asFirst            Move page as first child of $target if true (default), last child elsewhere.
     *
     * @return Page                                     The moved page.
     */
    private function moveSectionAsChildOf(Page $page, Page $target, $asFirst = true)
    {
        $delta = $target->getLevel() - $page->getLevel() + 1;
        $this->shiftLevel($page, $delta);

        $this->getEntityManager()
                ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                ->moveNode($page->getSection(), $target->getSection(), $asFirst ? 'firstin' : 'lastin');

        return $page;
    }

    /**
     * Move a page as previous sibling of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     *
     * @return Page                                     The moved page.
     */
    public function moveAsPrevSiblingOf(Page $page, Page $target)
    {
        return $this->moveAsSiblingOf($page, $target, true);
    }

    /**
     * Move a page as next sibling of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     *
     * @return Page                                     The moved page.
     */
    public function moveAsNextSiblingOf(Page $page, Page $target)
    {
        return $this->moveAsSiblingOf($page, $target, false);
    }

    /**
     * Move a page as sibling of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     * @param  boolean              $asPrevious         Move page as previous sibling of $target if true (default), next sibling elsewhere.
     *
     * @return Page                                     The moved page.
     *
     * @throws InvalidArgumentException                 Raises if $target is a root.
     */
    private function moveAsSiblingOf(Page $page, Page $target, $asPrevious = true)
    {
        if (null === $target->getParent()) {
            throw new InvalidArgumentException('Cannot move a page as sibling of a root.');
        }

        if (!$page->hasMainSection() && $target->hasMainSection()) {
            $this->moveAsFirstChildOf($page, $target->getParent());
        } elseif (!$page->hasMainSection() && !$target->hasMainSection()) {
            $this->movePageAsSiblingOf($page, $target, $asPrevious);
        } elseif ($page->hasMainSection() && !$target->hasMainSection()) {
            $this->moveAsLastChildOf($page, $target->getParent());
        } else {
            $this->moveSectionAsSiblingOf($page, $target, $asPrevious);
        }

        return $page->setParent($target->getParent());
    }

    /**
     * Move a non-section page as sibling of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     * @param  boolean              $asPrevious         Move page as previous sibling of $target if true (default), next sibling elsewhere.
     *
     * @return Page                                     The moved page.
     */
    private function movePageAsSiblingOf(Page $page, Page $target, $asPrevious = true)
    {
        $this->shiftPosition($page, -1, true);
        $this->_em->refresh($target);

        if (true === $asPrevious) {
            $page->setPosition($target->getPosition());
            $this->shiftPosition($target, 1);
        } else {
            $page->setPosition($target->getPosition() + 1);
            $this->shiftPosition($target, 1, true);
        }

        return $page;
    }

    /**
     * Move a section page as sibling of $target.
     *
     * @param  Page                 $page               The page to be moved.
     * @param  Page                 $target             The target page.
     * @param  boolean              $asPrevious         Move page as previous sibling of $target if true (default), next sibling elsewhere.
     *
     * @return Page                                     The moved page.
     */
    private function moveSectionAsSiblingOf(Page $page, Page $target, $asPrevious = true)
    {
        $delta = $page->getLevel() - $target->getLevel();
        $this->shiftLevel($page, $delta);

        $this->getEntityManager()
                ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                ->moveNode($page->getSection(), $target->getSection(), $asPrevious ? 'before' : 'after');

        return $page;
    }

    /**
     * Returns the root page for $site.
     *
     * @param  Site                 $site               The site to test.
     * @param  integer[]            $restrictedStates   Optional, limit to pages having provided states ( [] by default ).
     *
     * @return Page|null                                The root instance or null if the entity can not be found.
     */
    public function getRoot(Site $site, array $restrictedStates = [])
    {
        $q = $this->createQueryBuilder('p')
                ->andSiteIs($site)
                ->andParentIs(null)
                ->orderby('p._position', 'asc')
                ->setMaxResults(1);

        if (0 < count($restrictedStates)) {
            $q->andStateIsIn($restrictedStates);
        }

        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns an array of online children of $page.
     *
     * @param  Page                 $page               The parent page.
     * @param  integer|null         $maxResults         Optional, the maximum number of results.
     * @param  array                $order              Optional, the ordering criteria ( ['_leftnode', 'asc'] by default).
     *
     * @return Page[]                                   An array of matching pages.
     *
     * @deprecated
     */
    public function getOnlineChildren(Page $page, $maxResults = null, array $order = ['_leftnode', 'asc'])
    {
        if (true === $page->isLeaf()) {
            return [];
        }

        $query = $this->createQueryBuilder('p')
                ->andIsOnline()
                ->andIsDescendantOf($page, true, 1, $this->getOrderingDescendants(1, null), $maxResults, 0, false);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns an array of children of $page.
     *
     * @param  Page                 $page               The parent page.
     * @param  string               $orderSort          Optional, the sort field, title by default.
     * @param  string               $orderDir           Optional, the sort direction, asc by default.
     * @param  array                $paging             Optional, the paging criteria: ['start' => xx, 'limit' => xx], empty by default.
     * @param  integer[]            $restrictedStates   Optional, limit to pages having provided states, empty by default.
     * @param  string[]             $options            Optional, the search criteria:
     *                                                      * 'beforePubdateField'  => timestamp against page._modified,
     *                                                      * 'afterPubdateField'   => timestamp against page._modified,
     *                                                      * 'searchField'         => string to search for title
     *
     * @return Page[]|Paginator                         Returns Paginaor is $paging criteria provided, array otherwise.
     *
     * @deprecated
     */
    public function getChildren(Page $page, $orderSort = '_title', $orderDir = 'asc', $paging = [], $restrictedStates = [], $options = [])
    {
        if (true === $page->isLeaf()) {
            return [];
        }

        $paginate = (is_array($paging) && array_key_exists('start', $paging) && array_key_exists('limit', $paging));

        $query = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, true, 1, [$orderSort => $orderDir], $paginate ? $paging['limit'] : null, $paginate ? $paging['start'] : 0, false)
                ->andSearchCriteria($restrictedStates, $options);

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Returns count of children of $page.
     *
     * @param  Page                 $page               The parent page.
     * @param  string               $orderSort          Optional, the sort field, title by default.
     * @param  string               $orderDir           Optional, the sort direction, asc by default.
     * @param  integer[]            $restrictedStates   Optional, limit to pages having provided states, empty by default.
     * @param  string[]             $options            Optional, the search criteria:
     *                                                      * 'beforePubdateField'  => timestamp against page._modified,
     *                                                      * 'afterPubdateField'   => timestamp against page._modified,
     *                                                      * 'searchField'         => string to search for title
     *
     * @return integer                                  The children count.
     *
     * @deprecated
     */
    public function countChildren(Page $page, $orderSort = '_title', $orderDir = 'asc', $restrictedStates = [], $options = [])
    {
        if (true === $page->isLeaf()) {
            return 0;
        }

        return $this->createQueryBuilder('p')
                        ->select("COUNT(p)")
                        ->andIsDescendantOf($page, true, 1, [$orderSort => $orderDir], null, 0, false)
                        ->andSearchCriteria($restrictedStates, $options)
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    /**
     * Sets state of $page and is descendant to STATE_DELETED.
     *
     * @param  Page                 $page               The page to delete.
     *
     * @return integer                                  The number of page having their state changed.
     */
    public function toTrash(Page $page)
    {
        if (true === $page->isLeaf()) {
            $page->setState(Page::STATE_DELETED);
            $this->getEntityManager()->flush($page);

            return 1;
        }

        $subquery = $this->getEntityManager()
                ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                ->createQueryBuilder('n')
                ->select('n._uid')
                ->andIsDescendantOf($page->getSection());

        return $this->createQueryBuilder('p')
                        ->update()
                        ->set('p._state', Page::STATE_DELETED)
                        ->andWhere('p._section IN ('.$subquery->getDQL().')')
                        ->setParameters($subquery->getParameters())
                        ->getQuery()
                        ->execute();
    }

    /**
     * Copy a page to a new one.
     *
     * @param  Page                 $page               The page to be copied.
     * @param  string|null          $title              Optional, the title of the copy, if null (default) the title of the copied page.
     * @param  Page|null            $parent             Optional, the parent of the copy, if null (default) the parent of the copied page.
     *
     * @return Page                                     The copy of the page.
     *
     * @throws InvalidArgumentException                 Raises if the page is deleted.
     */
    private function copy(Page $page, $title = null, Page $parent = null)
    {
        if (Page::STATE_DELETED & $page->getState()) {
            throw new InvalidArgumentException('Cannot duplicate a deleted page.');
        }

        // Cloning the page
        $new_page = clone $page;
        $new_page->setTitle((null === $title) ? $page->getTitle() : $title)
                ->setLayout($page->getLayout());

        // Setting the clone as first child of the parent
        if (null !== $parent) {
            $new_page = $this->insertNodeAsFirstChildOf($new_page, $parent, $new_page->hasMainSection());
        }

        // Persisting entities
        $this->_em->persist($new_page);

        return $new_page;
    }

    /**
     * Replace subcontent of ContentSet by their clone if exist.
     *
     * @param  AbstractClassContent $content            The cloned content.
     * @param  array                $cloningData        The cloned data array.
     * @param  BBUserToken|null     $token              Optional, the BBuser token to allow the update of revisions if set.
     *
     * @return PageRepository
     */
    private function updateRelatedPostCloning(AbstractClassContent $content, array $cloningData, BBUserToken $token = null)
    {
        if (
                $content instanceof ContentSet &&
                true === array_key_exists('pages', $cloningData) &&
                true === array_key_exists('contents', $cloningData) &&
                0 < count($cloningData['pages']) &&
                0 < count($cloningData['contents'])
        ) {
            // reading copied elements
            $copied_pages = array_keys($cloningData['pages']);
            $copied_contents = array_keys($cloningData['contents']);

            // Updating subcontent if needed
            foreach ($content as $subcontent) {
                if (false === $this->_em->contains($subcontent)) {
                    $subcontent = $this->_em->find(get_class($subcontent), $subcontent->getUid());
                }

                if (
                        $subcontent instanceof AbstractClassContent &&
                        null !== $subcontent->getMainNode() &&
                        true === in_array($subcontent->getMainNode()->getUid(), $copied_pages) &&
                        true === in_array($subcontent->getUid(), $copied_contents)
                ) {
                    // Loading draft for content
                    if (
                            null !== $token &&
                            (null !== $draft = $this->_em->getRepository('BackBee\CoreDomain\ClassContent\Revision')->getDraft($content, $token, true))
                        ) {
                        $content->setDraft($draft);
                    }
                    $content->replaceChildBy($subcontent, $cloningData['contents'][$subcontent->getUid()]);
                }
            }
        }

        return $this;
    }

    /**
     * Update mainnode of the content if need during clonage.
     *
     * @param  AbstractClassContent $content            The cloned content.
     * @param  array                $cloningPages       The cloned pages array.
     * @param  BBUserToken|null     $token              Optional, the BBuser token to allow the update of revisions if set.
     *
     * @return PageRepository
     */
    private function updateMainNodePostCloning(AbstractClassContent $content, array $cloningPages, BBUserToken $token = null)
    {
        $mainnode = $content->getMainNode();

        if (
                null !== $mainnode &&
                0 < count($cloningPages) &&
                true === in_array($mainnode->getUid(), array_keys($cloningPages))
            ) {
            // Loading draft for content
            if (
                    null !== $token &&
                    (null !== $draft = $this->_em->getRepository('BackBee\CoreDomain\ClassContent\Revision')->getDraft($content, $token, true))
                ) {
                $content->setDraft($draft);
            }
            $content->setMainNode($cloningPages[$mainnode->getUid()]);
        }

        return $this;
    }

    /**
     * Duplicate a page and its descendants.
     *
     * @param  Page                 $page               The page to be duplicated.
     * @param  string|null          $title              Optional, the title of the copy, if null (default) the title of the copied page.
     * @param  Page|null            $parent             Optional, the parent of the copy, if null (default) the parent of the copied page.
     *
     * @return Page                                     The copy of the page.
     *
     * @throws InvalidArgumentException                 Raises if the page is recursively duplicated in itself.
     */
    private function duplicateRecursively(Page $page, $title = null, Page $parent = null)
    {
        if (null !== $parent && true === $parent->isDescendantOf($page)) {
            throw new InvalidArgumentException('Cannot recursively duplicate a page in itself');
        }

        // Storing current children before clonage
        $children = $this->getDescendants($page, 1);

        // Cloning the page
        $new_page = $this->copy($page, $title, $parent);
        $this->_em->flush($new_page);

        // Cloning children
        foreach (array_reverse($children) as $child) {
            if (!(Page::STATE_DELETED & $child->getState())) {
                $new_child = $this->duplicateRecursively($child, null, $new_page);
                $new_page->cloningData = array_merge_recursive($new_page->cloningData, $new_child->cloningData);
            }
        }

        return $new_page;
    }

    /**
     * Duplicate a page and optionnaly its descendants.
     *
     * @param  Page                 $page               The page to be duplicated.
     * @param  string|null          $title              Optional, the title of the copy, if null (default) the title of the copied page.
     * @param  Page|null            $parent             Optional, the parent of the copy, if null (default) the parent of the copied page.
     * @param  boolean              $recursive          Optional, if true (by default) duplicates recursively the descendants of the page.
     * @param  BBUserToken|null     $token              Optional, the BBuser token to allow the update of revisions if set.
     *
     * @return Page                                     The copy of the page.
     *
     * @throws InvalidArgumentException                 Raises if the page is recursively duplicated in itself.
     */
    public function duplicate(Page $page, $title = null, Page $parent = null, $recursive = true, BBUserToken $token = null)
    {
        if (false === $recursive || false === $page->hasMainSection()) {
            $new_page = $this->copy($page, $title, $parent);
        } else {
            // Recursive cloning
            $new_page = $this->duplicateRecursively($page, $title, $parent);
        }

        // Finally updating contentset and mainnode
        foreach ($new_page->cloningData['contents'] as $content) {
            $this->updateRelatedPostCloning($content, $new_page->cloningData, $token)
                 ->updateMainNodePostCloning($content, $new_page->cloningData['pages'], $token)
            ;
        }

        return $new_page;
    }

    /**
     * Removes page with no contentset for $site.
     *
     * @param Site                  $site
     *
     * @deprecated
     */
    public function removeEmptyPages(Site $site)
    {
        $q = $this->createQueryBuilder('p')
            ->select()
            ->andWhere('p._contentset IS NULL')
            ->andWhere('p._site = :site')
            ->orderBy('p._leftnode', 'desc')
            ->setParameter('site', $site);

        foreach ($q->getQuery()->execute() as $page) {
            $this->deletePage($page);
        }
    }

    public function deletePage(Page $page)
    {
        if ($page->hasMainSection()) {
            $this->getEntityManager()
                    ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                    ->deleteSection($page->getSection());
        }

        if ($page->getContentSet() !== null) {
            $this->getEntityManager()->getRepository('BackBee\CoreDomain\ClassContent\AbstractClassContent')->deleteContent($page->getContentSet());
        }

        $this->getEntityManager()->remove($page);
    }

    /**
     * Saves a page with a section and returns it.
     *
     * @param  Page                 $page               The page to be saved with section.
     * @param  Section|null         $currentParent      Optional, if provided the parent section of the new one.
     *
     * @return Page                                     The newly page with section.
     */
    public function saveWithSection(Page $page, Section $currentParent = null)
    {
        if ($page->hasMainSection()) {
            return $page;
        }

        if (false === $this->_em->contains($page)) {
            $this->_em->persist($page);
        }

        $parent = $page->getSection();
        if (null !== $currentParent && $this->_em->getUnitOfWork()->isScheduledForInsert($currentParent)) {
            $this->_em->detach($currentParent);
        }

        $section = new Section($page->getUid(), ['page' => $page, 'site' => $page->getSite()]);

        $this->getEntityManager()
                ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                ->insertNodeAsFirstChildOf($section, $parent);

        return $page->setPosition(0)
                    ->setLevel($section->getLevel());
    }

    /**
     * Shift position values for pages siblings of and after $page by $delta.
     *
     * @param  Page                 $page               The page for which to shift position.
     * @param  integer              $delta              The shift value of position.
     * @param  boolean              $strict             Does $page is include (true) or not (false, by default).
     *
     * @return PageRepository
     */
    private function shiftPosition(Page $page, $delta, $strict = false)
    {
        if (true === $page->hasMainSection()) {
            return $this;
        }

        $query = $this->createQueryBuilder('p')
                ->set('p._position', 'p._position + :delta_node')
                ->andWhere('p._section = :section')
                ->andWhere('p._position >= :position')
                ->setParameters([
                    'delta_node' => $delta,
                    'section' => $page->getSection(),
                    'position' => $page->getPosition(),
                ]);

        if (true === $strict) {
            $query->andWhere('p != :page')
                    ->setParameter('page', $page);
        } else {
            $page->setPosition($page->getPosition() + $delta);
        }

        $query->update()
                ->getQuery()
                ->execute();

        return $this;
    }

    /**
     * Shift level values for pages descendants of $page by $delta.
     *
     * @param  Page                 $page               The page for which to shift level.
     * @param  integer              $delta              The shift value of position.
     * @param  boolean              $strict             Does $page is include (true) or not (false, by default).
     *
     * @return PageRepository
     */
    private function shiftLevel(Page $page, $delta, $strict = false)
    {
        if (false === $page->hasMainSection() && true === $strict) {
            return $this;
        }

        $query = $this->createQueryBuilder('p')
                ->update()
                ->set('p._level', 'p._level + :delta');

        if (true === $page->hasMainSection()) {
            $subquery = $this->getEntityManager()
                    ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                    ->createQueryBuilder('n')
                    ->select('n._uid')
                    ->andIsDescendantOf($page->getSection());

            $query->andWhere('p._section IN ('.$subquery->getDQL().')')
                    ->setParameters($subquery->getParameters());

            if (true === $strict) {
                $query->andWhere('p <> :page')
                        ->setParameter('page', $page);
            }
        } else {
            $query->andWhere('p = :page')
                    ->setParameter('page', $page);
        }

        $query->setParameter('delta', $delta)->getQuery()
                ->execute();

        return $this;
    }

    /**
     * Returns the maximum position of children of $page.
     *
     * @param  Page                 $page
     *
     * @return integer
     */
    private function getMaxPosition(Page $page)
    {
        if (false === $page->hasMainSection()) {
            return 0;
        }

        $query = $this->createQueryBuilder('p');
        $max = $query->select($query->expr()->max('p._position'))
                ->andParentIs($page)
                ->getQuery()
                ->getResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        return (null === $max) ? 0 : $max;
    }

    /**
     * Updates nodes information of a tree.
     *
     * @param  string               $nodeUid            The starting point in the tree.
     * @param  integer              $leftNode           Optional, the first value of left node (1 by default).
     * @param  integer              $level              Optional, the first value of level (0 by default).
     *
     * @return \StdClass
     *
     * @deprecated since version 1.1
     */
    public function updateTreeNatively($nodeUid, $leftNode = 1, $level = 0)
    {
        return $this->_em
                        ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                        ->updateTreeNatively($nodeUid, $leftNode, $level);
    }
}
