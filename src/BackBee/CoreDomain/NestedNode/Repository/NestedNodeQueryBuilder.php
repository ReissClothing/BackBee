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

use Doctrine\ORM\QueryBuilder;

use BackBee\CoreDomain\NestedNode\AbstractNestedNode;

/**
 * This class is responsible for building DQL query strings for NestedNode.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeQueryBuilder extends QueryBuilder
{
    /**
     * The root alias of this query.
     *
     * @var string
     */
    private $_root_alias;

    /**
     * Add query part to exclude $node from selection.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsNot(AbstractNestedNode $node, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._uid != :uid'.$suffix)
                        ->setParameter('uid'.$suffix, $node->getUid());
    }

    /**
     * Add query part to select a specific tree (by its root).
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRootIs(AbstractNestedNode $node, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._root = :root'.$suffix)
                        ->setParameter('root'.$suffix, $node);
    }

    /**
     * Add query part to select a specific subbranch of tree.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andParentIs(AbstractNestedNode $node = null, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        if (null === $node) {
            return $this->andWhere($alias.'._parent IS NULL');
        }

        return $this->andWhere($alias.'._parent = :parent'.$suffix)
                        ->setParameter('parent'.$suffix, $node);
    }

    /**
     * Add query part to select nodes by level.
     *
     * @param int    $level
     * @param string $alias optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLevelEquals($level, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._level = :level'.$suffix)
                        ->setParameter('level'.$suffix, $level);
    }

    /**
     * Add query part to select nodes having level lower than or equal to $level.
     *
     * @param int     $level  the level to test
     * @param boolean $strict if TRUE, having strictly level lower than $level
     * @param string  $alias  optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLevelIsLowerThan($level, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._level <= :level'.$suffix)
                        ->setParameter('level'.$suffix, $level - (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select nodes having level upper than or equal to $level.
     *
     * @param int     $level  the level to test
     * @param boolean $strict if TRUE, having strictly level upper than $level
     * @param string  $alias  optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLevelIsUpperThan($level, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._level >= :level'.$suffix)
                        ->setParameter('level'.$suffix, $level + (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select node with leftnode equals to $leftnode.
     *
     * @param int    $leftnode
     * @param string $alias    optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLeftnodeEquals($leftnode, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._leftnode = :leftnode'.$suffix)
                        ->setParameter('leftnode'.$suffix, $leftnode);
    }

    /**
     * Add query part to select nodes having leftnode lower than or equal to $leftnode.
     *
     * @param int     $leftnode
     * @param boolean $strict   If TRUE, having strictly leftnode lower than $leftnode
     * @param string  $alias    optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLeftnodeIsLowerThan($leftnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._leftnode <= :leftnode'.$suffix)
                        ->setParameter('leftnode'.$suffix, $leftnode - (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select nodes having leftnode upper than or equal to $leftnode.
     *
     * @param int     $leftnode
     * @param boolean $strict   If TRUE, having strictly leftnode upper than $leftnode
     * @param string  $alias    optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andLeftnodeIsUpperThan($leftnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._leftnode >= :leftnode'.$suffix)
                        ->setParameter('leftnode'.$suffix, $leftnode + (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select node with rightnode equals to $rightnode.
     *
     * @param int    $rightnode
     * @param string $alias     optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRightnodeEquals($rightnode, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._rightnode = :rightnode'.$suffix)
                        ->setParameter('rightnode'.$suffix, $rightnode);
    }

    /**
     * Add query part to select nodes having rightnode lower than or equal to $rightnode.
     *
     * @param int     $rightnode
     * @param boolean $strict    If TRUE, having strictly rightnode lower than $rightnode
     * @param string  $alias     optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRightnodeIsLowerThan($rightnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._rightnode <= :rightnode'.$suffix)
                        ->setParameter('rightnode'.$suffix, $rightnode - (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select nodes having rightnode upper than or equal to $rightnode.
     *
     * @param int     $rightnode
     * @param boolean $strict    If TRUE, having strictly rightnode upper than $rightnode
     * @param string  $alias     optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andRightnodeIsUpperThan($rightnode, $strict = false, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._rightnode >= :rightnode'.$suffix)
                        ->setParameter('rightnode'.$suffix, $rightnode + (true === $strict ? 1 : 0));
    }

    /**
     * Add query part to select siblings of $node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  boolean                                               $strict if TRUE, $node is exclude
     * @param  array                                                 $order  ordering spec ( array($field => $sort) )
     * @param  int                                                   $limit  max number of results
     * @param  int                                                   $start  first result index
     * @param  string                                                $alias  optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsSiblingsOf(AbstractNestedNode $node, $strict = false, $order = null, $limit = null, $start = 0, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        if (true === $strict) {
            $this->andIsNot($node, $alias);
            $suffix++;
        }

        if (true === $node->isRoot()) {
            $this->andWhere($alias.'._uid = :uid'.$suffix)
                    ->setParameter('uid'.$suffix, $node->getUid());
        }

        if (null !== $limit) {
            $this->setMaxResults($limit)
                    ->setFirstResult($start);
        }

        return $this->andParentIs($node->getParent(), $alias)
                        ->orderByMultiple($order, $alias);
    }

    /**
     * Add query part to select previous sibling of node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsPreviousSiblingOf(AbstractNestedNode $node, $alias = null)
    {
        return $this->andRootIs($node->getRoot(), $alias)
                        ->andRightnodeEquals($node->getLeftnode() - 1, $alias);
    }

    /**
     * Add query part to select previous siblings of node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsPreviousSiblingsOf(AbstractNestedNode $node, $alias = null)
    {
        return $this->andParentIs($node->getParent(), $alias)
                        ->andLeftnodeIsLowerThan($node->getLeftnode(), true, $alias);
    }

    /**
     * Add query part to select next sibling of node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsNextSiblingOf(AbstractNestedNode $node, $alias = null)
    {
        return $this->andRootIs($node->getRoot(), $alias)
                        ->andLeftnodeEquals($node->getRightnode() + 1, $alias);
    }

    /**
     * Add query part to select next siblings of node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  string                                                $alias optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsNextSiblingsOf(AbstractNestedNode $node, $alias = null)
    {
        return $this->andParentIs($node->getParent(), $alias)
                        ->andLeftnodeIsUpperThan($node->getRightnode(), true, $alias);
    }

    /**
     * Add query part to select ancestors of $node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  boolean                                               $strict   If TRUE, $node is excluded from the selection
     * @param  int                                                   $at_level Filter ancestors by their level
     * @param  string                                                $alias    optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsAncestorOf(AbstractNestedNode $node, $strict = false, $at_level = null, $alias = null)
    {
        $this->andRootIs($node->getRoot(), $alias)
                ->andLeftnodeIsLowerThan($node->getLeftnode(), $strict, $alias)
                ->andRightnodeIsUpperThan($node->getRightnode(), $strict, $alias);

        if (null !== $at_level) {
            $this->andLevelEquals($at_level);
        }

        return $this;
    }

    /**
     * Add query part to select descendants of $node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                       $node
     * @param  boolean                                               $strict   If TRUE, $node is excluded from the selection
     * @param  int                                                   $at_level Filter ancestors by their level
     * @param  string                                                $alias    optional, the alias to use
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function andIsDescendantOf(AbstractNestedNode $node, $strict = false, $at_level = null, $alias = null)
    {
        $this->andRootIs($node->getRoot(), $alias)
                ->andLeftnodeIsUpperThan($node->getLeftnode(), $strict, $alias)
                ->andRightnodeIsLowerThan($node->getRightnode(), $strict, $alias);

        if (null !== $at_level) {
            $this->andLevelEquals($at_level);
        }

        return $this;
    }

    /**
     * Add multiple ordering criteria.
     *
     * @param array  $order optional, the ordering criteria ( array('_leftnode' => 'asc') by default )
     * @param string $alias optional, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function orderByMultiple($order = array('_leftnode' => 'asc'), $alias = null)
    {
        if (true === empty($order)) {
            $order = array('_leftnode' => 'asc');
        }

        $this->resetDQLPart('orderBy');
        $alias = $this->getFirstAlias($alias);
        foreach ($order as $field => $sort) {
            if (0 < count($this->getDQLPart('orderBy'))) {
                $this->addOrderBy($alias.'.'.$field, $sort);
            } else {
                $this->orderBy($alias.'.'.$field, $sort);
            }
        }

        return $this;
    }

    /**
     * Add qery part to select page having modified date lower than $date.
     *
     * @param \DateTime $date  the date to test
     * @param string    $alias optiona, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\PageQueryBuilder
     */
    public function andModifiedIsLowerThan(\DateTime $date, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._modified < :date'.$suffix)
                        ->setParameter('date'.$suffix, $date);
    }

    /**
     * Add qery part to select page having modified date greater than $date.
     *
     * @param \DateTime $date  the date to test
     * @param string    $alias optiona, the alias to use
     *
     * @return \BackBee\NestedNode\Repository\PageQueryBuilder
     */
    public function andModifiedIsGreaterThan(\DateTime $date, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._modified > :date'.$suffix)
                        ->setParameter('date'.$suffix, $date);
    }

    /**
     * Try to retreive the root alias for this builder.
     *
     * @return string
     *
     * @throws \BackBee\Exception\BBException
     */
    protected function getFirstAlias($alias = null)
    {
        if (false === empty($alias)) {
            return $alias;
        }

        if (null === $this->_root_alias) {
            $aliases = $this->getRootAliases();
            if (0 === count($aliases)) {
                throw new \BackBee\Exception\BBException('Cannot access to root alias');
            }

            $this->_root_alias = $aliases[0];
        }

        return $this->_root_alias;
    }

    /**
     * Compute suffix and alias used by query part.
     *
     * @param string $alias
     *
     * @return array
     */
    protected function getAliasAndSuffix($alias = null)
    {
        $suffix = count($this->getParameters());
        $alias = $this->getFirstAlias($alias);

        return array($alias, $suffix);
    }
}
