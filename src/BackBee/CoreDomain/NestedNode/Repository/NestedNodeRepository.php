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
use BackBee\CoreDomain\NestedNode\AbstractNestedNode;
use BackBee\Util\Buffer;
use InvalidArgumentException;

/**
 * NestedNode repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeRepository extends EntityRepository
{
    public function updateTreeNatively($nodeUid, $leftnode = 1, $level = 0)
    {
        $node = new \StdClass();
        $node->uid = $nodeUid;
        $node->leftnode = $leftnode;
        $node->rightnode = $leftnode + 1;
        $node->level = $level;

        foreach ($this->getNativelyNodeChildren($nodeUid) as $row) {
            $child = $this->updateTreeNatively($row['uid'], $leftnode + 1, $level + 1);
            $node->rightnode = $child->rightnode + 1;
            $leftnode = $child->rightnode;
        }

        $this->_em->getConnection()->exec(sprintf(
            'UPDATE page SET leftnode = %d, rightnode = %d, level = %d WHERE uid = "%s";',
            $node->leftnode,
            $node->rightnode,
            $node->level,
            $node->uid
        ));

        return $node;
    }

    private function getNativelyNodeChildren($nodeUid)
    {
        return $this->_em->getConnection()->executeQuery(sprintf(
            'select uid from page where parent_uid = "%s" order by leftnode asc, modified desc', $nodeUid
        ))->fetchAll();
    }

    public function updateTreeNativelyWithProgressMessage($nodeUid)
    {
        $nodeUid = (array) $nodeUid;
        if (0 === count($nodeUid)) {
            Buffer::dump("\n##### Nothing to update. ###\n");

            return;
        }

        $convert_memory = function ($size) {
            $unit = array('B', 'KB', 'MB', 'GB');

            return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
        };

        $starttime = microtime(true);

        Buffer::dump("\n##### Update tree (natively) started ###\n");

        foreach ($nodeUid as $uid) {
            $this->_em->clear();

            $starttime = microtime(true);
            Buffer::dump("\n   [START] update tree of $uid in progress\n\n");

            $this->updateTreeNatively($uid);

            Buffer::dump(
                    "\n   [END] update tree of $uid in "
                    .(microtime(true) - $starttime).'s (memory status: '.$convert_memory(memory_get_usage()).')'
                    ."\n"
            );
        }

        Buffer::dump("\n##### Update tree (natively) in ".(microtime(true) - $starttime)."s #####\n\n");
    }

    public function updateHierarchicalDatas(AbstractNestedNode $node, $leftnode = 1, $level = 0)
    {
        $node->setLeftnode($leftnode)->setLevel($level);

        if (0 < $node->getChildren()->count()) {
            $children = $this->createQueryBuilder('n')
                    ->andWhere("n._parent = :parent")
                    ->setParameters(array("parent" => $node))
                    ->orderBy('n._leftnode', 'asc')
                    ->getQuery()
                    ->getResult();

            foreach ($children as $child) {
                $child = $this->updateHierarchicalDatas($child, $leftnode + 1, $level + 1);
                $leftnode = $child->getRightnode();
            }
        }

        $node->setRightnode($leftnode + 1);
        $this->createQueryBuilder('n')
                ->update()
                ->set('n._leftnode', $node->getLeftnode())
                ->set('n._rightnode', $node->getRightnode())
                ->set('n._level', $node->getLevel())
                ->where('n._uid = :uid')
                ->setParameter('uid', $node->getUid())
                ->getQuery()
                ->execute();

        $this->_em->detach($node);

        return $node;
    }

    /**
     * Inserts a leaf node in a tree as first child of the provided parent node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                   $node   The node to be inserted
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode             $parent The parent node
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode             The inserted node
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the node is not a leaf or $parent is not flushed yet
     */
    public function insertNodeAsFirstChildOf(AbstractNestedNode $node, AbstractNestedNode $parent)
    {
        return $this->_insertNode($node, $parent, $parent->getLeftnode() + 1);
    }

    /**
     * Inserts a leaf node in a tree as last child of the provided parent node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode             $node   The node to be inserted
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode             $parent The parent node
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode             The inserted node
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the node is not a leaf or $parent is not flushed yet
     */
    public function insertNodeAsLastChildOf(AbstractNestedNode $node, AbstractNestedNode $parent)
    {
        return $this->_insertNode($node, $parent, $parent->getRightnode());
    }

    /**
     * Inserts a leaf node in a tree.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode             $node         The node to be inserted
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode             $parent       The parent node
     * @param  int                                                $newLeftNode  The new left node of the inserted node
     *
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode             The inserted node
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the node is an ancestor of $parent or $parent is not flushed yet
     */
    protected function _insertNode(AbstractNestedNode $node, AbstractNestedNode $parent, $newLeftNode)
    {
        if ($parent->isDescendantOf($node, false)) {
            throw new InvalidArgumentException('Cannot insert node in itself or one of its descendants');
        }

        if (false === $this->_em->contains($parent)) {
            throw new InvalidArgumentException('Cannot insert in a non managed node');
        }

        $this->detachOrPersistNode($node);

        $newRightNode = $newLeftNode + $node->getWeight() - 1;
        $node->setLeftnode($newLeftNode)

                ->setRightnode($newRightNode)
                ->setLevel($parent->getLevel() + 1)
                ->setParent($parent)
                ->setRoot($parent->getRoot());

        $this->shiftRlValues($node, $node->getLeftnode(), $node->getWeight());

        $this->_em->refresh($parent);

        return $node;
    }

    /**
     * Returns the previous sibling node for $node or NULL if $node is the first one in its branch or root.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode      $node
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode|NULL
     */
    public function getPrevSibling(AbstractNestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andIsPreviousSiblingOf($node)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the next sibling node for $node or NULL if $node is the last one in its branch.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode      $node
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode|NULL
     */
    public function getNextSibling(AbstractNestedNode $node)
    {
        return $this->createQueryBuilder('n')
                        ->andIsNextSiblingOf($node)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the siblings of the provided node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode   $node
     * @param  boolean                           $includeNode if TRUE, include $node in result array
     * @param  array                             $order       ordering spec
     * @param  int                               $limit       max number of results
     * @param  int                               $start       first result index
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode[]
     */
    public function getSiblings(AbstractNestedNode $node, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('n')
                        ->andIsSiblingsOf($node, !$includeNode, $order, $limit, $start)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the first child of node if exists.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode      $node
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode|NULL
     */
    public function getFirstChild(AbstractNestedNode $node)
    {
        $children = $this->getDescendants($node, 1);
        if (0 < count($children)) {
            return $children[0];
        }

        return;
    }

    /**
     * Returns the first child of node if exists.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode|NULL
     */
    public function getLastChild(AbstractNestedNode $node)
    {
        $children = $this->getDescendants($node, 1);
        if (0 < count($children)) {
            return $children[count($children) - 1];
        }

        return;
    }

    /**
     * Returns the ancestor at level $level of the provided node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode      $node
     * @param  int                                        $level
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode|NULL
     */
    public function getAncestor(AbstractNestedNode $node, $level = 0)
    {
        if ($node->getLevel() < $level) {
            return;
        }

        if ($node->getLevel() == $level) {
            return $node;
        }

        try {
            return $this->createQueryBuilder('n')
                            ->andIsAncestorOf($node, false, $level)
                            ->getQuery()
                            ->getSingleResult();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Returns the ancestors of the provided node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  int                             $depth       Returns only ancestors from $depth number of generation
     * @param  boolean                         $includeNode Returns also the node itsef if TRUE
     * @return array
     */
    public function getAncestors(AbstractNestedNode $node, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('n')
                ->andIsAncestorOf($node, !$includeNode);

        if (null !== $depth) {
            $q->andLevelIsUpperThan($node->getLevel() - $depth);
        }

        return $q->orderBy('n._rightnode', 'desc')
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the descendants of the provided node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  int                             $depth       Returns only decendants from $depth number of generation
     * @param  boolean                         $includeNode Returns also the node itsef if TRUE
     * @return array
     */
    public function getDescendants(AbstractNestedNode $node, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('n')
                ->andIsDescendantOf($node, !$includeNode);

        if (null !== $depth) {
            $q->andLevelIsLowerThan($node->getLevel() + $depth);
        }

        return $q->orderBy('n._leftnode', 'asc')
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Move node as previous sibling of $dest.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws InvalidArgumentException        Occurs if $dest is a root
     */
    public function moveAsPrevSiblingOf(AbstractNestedNode $node, AbstractNestedNode $dest)
    {
        if (true === $dest->isRoot()) {
            throw new InvalidArgumentException('Cannot move node as sibling of a root');
        }

        return $this->_moveNode($node, $dest, 'before');
    }

    /**
     * Move node as next sibling of $dest.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws InvalidArgumentException        Occurs if $dest is a root
     */
    public function moveAsNextSiblingOf(AbstractNestedNode $node, AbstractNestedNode $dest)
    {
        if (true === $dest->isRoot()) {
            throw new InvalidArgumentException('Cannot move node as sibling of a root');
        }

        return $this->_moveNode($node, $dest, 'after');
    }

    /**
     * Move node as first child of $dest.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function moveAsFirstChildOf(AbstractNestedNode $node, AbstractNestedNode $dest)
    {
        return $this->_moveNode($node, $dest, 'firstin');
    }

    /**
     * Move node as last child of $dest.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     */
    public function moveAsLastChildOf(AbstractNestedNode $node, AbstractNestedNode $dest)
    {
        return $this->_moveNode($node, $dest, 'lastin');
    }

    /**
     * Move node regarding $dest.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @param  string                    $position
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws InvalidArgumentException        Occurs if $node is ancestor of $dest
     */
    protected function _moveNode(AbstractNestedNode $node, AbstractNestedNode $dest, $position)
    {
        if (true === $node->isAncestorOf($dest, false)) {
            throw new InvalidArgumentException('Cannot move node as child of one of its descendants');
        }

        $this->refreshExistingNode($node)
                ->detachFromTree($node)
                ->refreshExistingNode($dest);

        $newleft = $this->getNewLeftFromPosition($dest, $position);
        $newlevel = $this->getNewLevelFromPosition($dest, $position);
        $newparent = $this->getNewParentFromPosition($dest, $position);

        $node->setRightnode($newleft + $node->getWeight() - 1)
                ->setLeftnode($newleft)
                ->setLevel($newlevel)
                ->setRoot($dest->getRoot())
                ->setParent($newparent);

        $this->shiftRlValues($node, $newleft, $node->getWeight());

        $this->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode + :delta_node')
                ->set('n._rightnode', 'n._rightnode + :delta_node')
                ->set('n._level', 'n._level + :delta_level')
                ->set('n._root', ':root')
                ->andWhere('n._root = :node')
                ->setParameters(array(
                    'delta_node' => $newleft - 1,
                    'delta_level' => $newlevel,
                    'root' => $dest->getRoot(),
                    'node' => $node,
                ))
                ->update()
                ->getQuery()
                ->execute();

        return $node;
    }

    /**
     * Returns the new left node from $dest node and position.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @param  string                          $position
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws InvalidArgumentException        Occurs if $position is unknown
     */
    private function getNewLeftFromPosition(AbstractNestedNode $dest, $position)
    {
        switch ($position) {
            case 'before':
                $newleft = $dest->getLeftnode();
                break;
            case 'after':
                $newleft = $dest->getRightnode() + 1;
                break;
            case 'firstin':
                $newleft = $dest->getLeftnode() + 1;
                break;
            case 'lastin':
                $newleft = $dest->getRightnode();
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position %s to move node', $position));
        }

        return $newleft;
    }

    /**
     * Returns the new level of node from $dest node and position.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @param  string                          $position
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws InvalidArgumentException        Occurs if $position is unknown
     */
    private function getNewLevelFromPosition(AbstractNestedNode $dest, $position)
    {
        switch ($position) {
            case 'before':
            case 'after':
                $newlevel = $dest->getLevel();
                break;
            case 'firstin':
            case 'lastin':
                $newlevel = $dest->getLevel() + 1;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position %s to move node', $position));
        }

        return $newlevel;
    }

    /**
     * Returns the new parent node from $dest node and position.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $dest
     * @param  string                          $position
     * @return \BackBee\CoreDomain\NestedNode\AbstractNestedNode
     * @throws InvalidArgumentException        Occurs if $position is unknown
     */
    private function getNewParentFromPosition(AbstractNestedNode $dest, $position)
    {
        switch ($position) {
            case 'before':
            case 'after':
                $newparent = $dest->getParent();
                break;
            case 'firstin':
            case 'lastin':
                $newparent = $dest;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown position %s to move node', $position));
        }

        return $newparent;
    }

    /**
     * Deletes node and it's descendants.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @return boolean                         TRUE on success, FALSE if try to delete a root
     */
    public function delete(AbstractNestedNode $node)
    {
        if (true === $node->isRoot()) {
            return false;
        }

        $this->createQueryBuilder('n')
                ->set('n._parent', 'NULL')
                ->andIsDescendantOf($node)
                ->update()
                ->getQuery()
                ->execute();

        $this->createQueryBuilder('n')
                ->delete()
                ->andIsDescendantOf($node)
                ->getQuery()
                ->execute();

        $this->shiftRlValues($node->getParent(), $node->getLeftnode(), -$node->getWeight());

        return true;
    }

    /**
     * Shift part of a tree.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                     $node
     * @param  integer                                                    $first
     * @param  integer                                                    $delta
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                     $target
     * @return \BackBee\CoreDomain\NestedNode\Repository\NestedNodeRepository
     */
    private function shiftRlValues(AbstractNestedNode $node, $first, $delta)
    {
        $this->createQueryBuilder('n')
            ->set('n._leftnode', 'n._leftnode + :delta')
            ->andRootIs($node->getRoot())
            ->andLeftnodeIsUpperThan($first)
            ->setParameter('delta', $delta)
            ->update()
            ->getQuery()
            ->execute()
        ;

        $this->createQueryBuilder('n')
            ->set('n._rightnode', 'n._rightnode + :delta')
            ->andRootIs($node->getRoot())
            ->andRightnodeIsUpperThan($first)
            ->setParameter('delta', $delta)
            ->update()
            ->getQuery()
            ->execute()
        ;

        return $this;
    }

    /**
     * Detach node from its tree, ie create a new tree from node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                     $node
     * @return \BackBee\CoreDomain\NestedNode\Repository\NestedNodeRepository
     */
    protected function detachFromTree(AbstractNestedNode $node)
    {
        if (true === $node->isRoot()) {
            return $this;
        }

        $this->refreshExistingNode($node)
                ->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode - :delta_node')
                ->set('n._rightnode', 'n._rightnode - :delta_node')
                ->set('n._level', 'n._level - :delta_level')
                ->set('n._root', ':node')
                ->andIsDescendantOf($node)
                ->setParameter('delta_node', $node->getLeftnode() - 1)
                ->setParameter('delta_level', $node->getLevel())
                ->setParameter('node', $node)
                ->update()
                ->getQuery()
                ->execute();

        $this->shiftRlValues($node, $node->getLeftnode(), - $node->getWeight());

        $node->setRightnode($node->getWeight())
                ->setLeftnode(1)
                ->setLevel(0)
                ->setRoot($node);

        return $this;
    }

    /**
     * Refresh an existing node.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                     $node
     * @return \BackBee\CoreDomain\NestedNode\Repository\NestedNodeRepository
     */
    protected function refreshExistingNode(AbstractNestedNode $node)
    {
        if (true === $this->_em->contains($node)) {
            $this->_em->refresh($node);
        } elseif (null === $node = $this->find($node->getUid())) {
            $this->_em->persist($node);
        }

        return $this;
    }

    /**
     * Persist a new node or detach it from tree if already exists.
     *
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode                     $node
     * @return \BackBee\CoreDomain\NestedNode\Repository\NestedNodeRepository
     */
    protected function detachOrPersistNode(AbstractNestedNode $node)
    {
        if (null !== $refreshed = $this->find($node->getUid())) {
            return $this->detachFromTree($refreshed)
                            ->refreshExistingNode($node);
        }

        if (false === $this->_em->contains($node)) {
            $this->_em->persist($node);
        }

        return $this;
    }

    /**
     * Creates a new NestedNode QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return \BackBee\NestedNode\Repository\NestedNodeQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $qb = new NestedNodeQueryBuilder($this->_em);

        return $qb->select($alias)->from($this->_entityName, $alias, $indexBy);
    }
}
