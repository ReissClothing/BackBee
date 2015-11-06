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

use BackBee\ClassContent\Element\Keyword;
use BackBee\CoreDomain\NestedNode\Page;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;

/**
 * Keyword repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class KeyWordRepository extends NestedNodeRepository
{
    public function getLikeKeyWords($cond, $max = 10)
    {
        try {
            $q = $this->createQueryBuilder('k')->andWhere('k._keyWord like :key')->orderBy('k._keyWord', 'ASC')->setMaxResults($max)
                    ->setParameters(array('key' => $cond.'%'))
                    ->getQuery();

            return $q->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    public function getKeyWords($parent, $orderInfos, $paging = array())
    {
        $qb = $this->createQueryBuilder('kw');
        $qb->andParentIs($parent);

        /* order */
        if (is_array($orderInfos)) {
            if (array_key_exists('field', $orderInfos) && array_key_exists('dir', $orderInfos)) {
                $qb->orderBy('kw.'.$orderInfos['field'], $orderInfos['dir']);
            }
        }
        /* paging */
        if (is_array($paging) && !empty($paging)) {
            if (array_key_exists('start', $paging) && array_key_exists('limit', $paging)) {
                $qb->setFirstResult($paging['start'])
                       ->setMaxResults($paging['limit']);
                $result = new Paginator($qb);
            }
        } else {
            $result = $qb->getQuery()->getResult();
        }

        return $result;
    }

    public function getRoot()
    {
        try {
            $q = $this->createQueryBuilder('k')
                    ->andWhere('k._parent is NULL')
                    ->getQuery();

            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    public function getKeywordTreeAsArray($node = null)
    {
        $node = (is_null($node)) ? $this->getRoot() : $node;
        $nodeInfos = new \stdClass();
        $nodeInfos->uid = $node->getUid();
        $nodeInfos->level = $node->getLevel();
        $nodeInfos->keyword = $node->getKeyword();
        $nodeInfos->children = array();
        $children = $this->getDescendants($node, 1);
        if (is_array($children)) {
            foreach ($children as $child) {
                $nodeInfos->children[] = $this->getKeywordTreeAsArray($child);
            }
        }

        return $nodeInfos;
    }

    public function getContentsIdByKeyWords($keywords, $limitToOnline = true)
    {
        try {
            if (isset($keywords) && !empty($keywords)) {
                $keywords = (is_array($keywords)) ? $keywords : array($keywords);
                $db = $this->_em->getConnection();
                $queryString = 'SELECT content.uid
                    FROM
                        keywords_contents
                    LEFT JOIN
                        content on (content.uid = keywords_contents.content_uid)
                    LEFT JOIN
                        page on (content.node_uid = page.uid)
                    WHERE
                        keywords_contents.keyword_uid IN (?)';

                if ($limitToOnline) {
                    $queryString .= ' AND page.state IN (?)';
                    $pageStates = array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN);
                    $secondParam = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
                } else {
                    $pageStates = Page::STATE_DELETED;
                    $queryString .= ' AND page.state < (?)';
                    $secondParam = 1;
                }
                $stmt = $db->executeQuery($queryString, array($keywords, $pageStates), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, $secondParam));
                $result = array();
                while ($contendId = $stmt->fetchColumn()) {
                    $result[] = $contendId;
                }

                return $result;
            }
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Returns the nested keywords object according to the element keyword objects provided
     * Also set the parameter 'objectKeyword' from the element to the nested keyword.
     *
     * @param array $elements
     *
     * @return array
     */
    public function getKeywordsFromElements(&$elements = array())
    {
        if (0 === count($elements)) {
            return array();
        }

        $uids = array();
        $assoc = array();
        foreach ($elements as &$element) {
            if ($element instanceof Keyword) {
                $uids[] = $element->value;
                $assoc[$element->value] = &$element;
            } elseif (true === is_string($element)) {
                $uids[] = trim($element);
            }
        }
        unset($element);

        $objects = $this->createQueryBuilder('k')
                ->where('k._uid IN (:uids)')
                ->setParameter('uids', $uids)
                ->getQuery()
                ->getResult();

        foreach ($objects as $object) {
            if (true === array_key_exists($object->getUid(), $assoc)) {
                $assoc[$object->getUid()]->setParam('objectkeyword', [$object]);
            }
        }

        return $objects;
    }

    /**
     * Check if given keyword already exists in database; it's case sensitive and make difference
     * between "e" and "é".
     *
     * @param string $keyword string
     *
     * @return object|null return object if it already exists, else null
     */
    public function exists($keyword)
    {
        $object = null;
        $result = $this->_em->getConnection()->executeQuery(sprintf(
            'SELECT uid FROM keyword WHERE hex(lower(keyword)) = hex(lower("%s"))',
            preg_replace('#[/\"]#', '', trim($keyword))
        ))->fetchAll();

        if (0 < count($result)) {
            $uid = array_shift($result);
            $object = $this->find($uid['uid']);
        }

        return $object;
    }
}
