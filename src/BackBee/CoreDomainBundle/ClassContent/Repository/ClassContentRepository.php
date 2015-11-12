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

use BackBee\BBApplication;
use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\ClassContent\ContentSet;
use BackBee\CoreDomain\ClassContent\Revision;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\Security\Token\BBUserToken;
use BackBee\Util\Doctrine\SettablePaginator;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * AbstractClassContent repository
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ClassContentRepository extends EntityRepository
{
    /**
     * Get all content uids owning the provided content.
     *
     * @param  string $contentUid
     *
     * @return array
     */
    public function getParentContentUidByUid($contentUid)
    {
        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.parent_uid')
                ->from('content_has_subcontent', 'c')
                ->where('c.content_uid = :uid')
                ->setParameter('uid', $contentUid);

        return $q->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns provided content direct parents entity.
     *
     * @param  AbstractClassContent $content The content we want to get its direct parents
     * @return array
     */
    public function getParentContents(AbstractClassContent $content)
    {
        $result = $this->_em->getConnection()
            ->createQueryBuilder()
            ->select('p.parent_uid', 'c.classname')
            ->from('content_has_subcontent', 'p')
            ->leftJoin('p', 'content', 'c', 'p.parent_uid = c.uid')
            ->where('p.content_uid = :uid')
            ->setParameter('uid', $content->getUid())
            ->execute()
            ->fetchAll()
        ;

        $parents = [];
        foreach ($result as $parentData) {
            $parentData['classname'] = AbstractClassContent::getFullClassname($parentData['classname']);
            $parents[] = $this->_em->find($parentData['classname'], $parentData['parent_uid']);
        }

        return $parents;
    }

    /**
     * Get all content uids owning the provided content.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     *
     * @return array
     */
    public function getParentContentUid(AbstractClassContent $content)
    {
        return $this->getParentContentUidByUid($content->getUid());
    }

    /**
     * Replace root contentset for a page and its descendants.
     *
     * @param \BackBee\CoreDomain\NestedNode\Page            $page
     * @param \BackBee\CoreDomain\ClassContent\ContentSet    $oldContentSet
     * @param \BackBee\CoreDomain\ClassContent\ContentSet    $newContentSet
     * @param \BackBee\Security\Token\BBUserToken $userToken
     */
    public function updateRootContentSetByPage(Page $page, ContentSet $oldContentSet, ContentSet $newContentSet, BBUserToken $userToken)
    {
        $em = $this->_em;
        $q = $this->createQueryBuilder('c');
        $results = $q->leftJoin('c._pages', 'p')
                        ->leftJoin('p._section', 'sp')
                        ->leftJoin('c._subcontent', 'subcontent')
                        ->where('subcontent = :contentToReplace')
                        ->andWhere('sp._root = :cpageRoot')
                        ->andWhere('sp._leftnode > :cpageLeftnode')
                        ->andWhere('sp._rightnode < :cpageRightnode')
                        ->setParameters([
                            'contentToReplace' => $oldContentSet,
                            'cpageRoot' => $page->getSection()->getRoot(),
                            'cpageLeftnode' => $page->getLeftnode(),
                            'cpageRightnode' => $page->getRightnode(),
                        ])
                        ->getQuery()->getResult()
        ;

        if ($results) {
            foreach ($results as $parentContentSet) {
                /* create draft for the main container */
                $draft = $em->getRepository('BackBee\CoreDomain\ClassContent\Revision')->getDraft(
                    $parentContentSet,
                    $userToken,
                    true
                );

                if (null !== $draft) {
                    $parentContentSet->setDraft($draft);
                }

                /* Replace the old ContentSet by the new one */
                $parentContentSet->replaceChildBy($oldContentSet, $newContentSet);
                $em->persist($parentContentSet);
            }
        }
    }

    /**
     * Get a selection of ClassContent.
     *
     * @param array   $selector
     * @param boolean $multipage
     * @param boolean $recursive
     * @param int     $start
     * @param int     $limit
     * @param boolean $limitToOnline
     * @param boolean $excludedFromSelection
     * @param array   $classnameArr
     * @param int     $delta
     *
     * @return array|Paginator
     */
    public function getSelection(
        $selector,
        $multipage = false,
        $recursive = true,
        $start = 0,
        $limit = null,
        $limitToOnline = true,
        $excludedFromSelection = false,
        $classnameArr = array(),
        $delta = 0)
    {
        $query = 'SELECT c.uid FROM content c';
        $join = array();
        $where = array();
        $orderby = array();
        $limit = $limit ? $limit : (array_key_exists('limit', $selector) ? $selector['limit'] : 10);
        $offset = $start + $delta;

        if (true === is_array($classnameArr) && 0 < count($classnameArr)) {
            foreach ($classnameArr as &$classname) {
                // ensure Doctrine already known these classname
                class_exists($classname);
                $classname = AbstractClassContent::getShortClassname($classname);
            }
            unset($classname);
            $where[] = str_replace('\\', '\\\\', 'c.classname IN ("'.implode('","', $classnameArr).'")');
        }

        if (true === array_key_exists('content_uid', $selector)) {
            $uids = (array) $selector['content_uid'];
            if (false === empty($uids)) {
                $where[] = 'c.uid IN ("'.implode('","', $uids).'")';
            }
        }

        if (true === array_key_exists('criteria', $selector)) {
            $criteria = (array) $selector['criteria'];
            foreach ($criteria as $field => $crit) {
                $crit = (array) $crit;
                if (1 == count($crit)) {
                    $crit[1] = '=';
                }

                $alias = uniqid('i'.rand());
                $join[] = 'LEFT JOIN indexation '.$alias.' ON c.uid  = '.$alias.'.content_uid';
                $where[] = $alias.'.field = "'.$field.'" AND '.$alias.'.value '.$crit[1].' "'.$crit[0].'"';
            }
        }

        if (true === array_key_exists('indexedcriteria', $selector) &&
                true === is_array($selector['indexedcriteria'])) {
            foreach ($selector['indexedcriteria'] as $field => $values) {
                $values = array_filter((array) $values);
                if (0 < count($values)) {
                    $alias = md5($field);
                    $join[] = 'LEFT JOIN indexation '.$alias.' ON c.uid  = '.$alias.'.content_uid';
                    $where[] = $alias.'.field = "'.$field.'" AND '.$alias.'.value IN ("'.implode('","', $values).'")';
                }
            }
        }

        if (true === array_key_exists('keywordsselector', $selector)) {
            $keywordInfos = $selector['keywordsselector'];
            if (true === is_array($keywordInfos)) {
                if (true === array_key_exists('selected', $keywordInfos)) {
                    $selectedKeywords = $keywordInfos['selected'];
                    if (true === is_array($selectedKeywords)) {
                        $selectedKeywords = array_filter($selectedKeywords);
                        if (false === empty($selectedKeywords)) {
                            $contentIds = $this->_em->getRepository('BackBee\CoreDomain\NestedNode\KeyWord')->getContentsIdByKeyWords($selectedKeywords, false);
                            if (true === is_array($contentIds) && false === empty($contentIds)) {
                                $where[] = 'c.uid IN ("'.implode('","', $contentIds).'")';
                            } else {
                                return array();
                            }
                        }
                    }
                }
            }
        }

        if (false === array_key_exists('orderby', $selector)) {
            $selector['orderby'] = array('created', 'desc');
        } else {
            $selector['orderby'] = (array) $selector['orderby'];
        }

        $hasPageJoined = false;
        if (array_key_exists('parentnode', $selector) && true === is_array($selector['parentnode'])) {
            $parentnode = array_filter($selector['parentnode']);
            if (false === empty($parentnode)) {
                $nodes = $this->_em->getRepository('BackBee\CoreDomain\NestedNode\Page')->findBy(array('_uid' => $parentnode));
                if (count($nodes) != 0) {
                    $subquery = $this->getEntityManager()
                            ->getRepository('BackBee\CoreDomain\NestedNode\Section')
                            ->createQueryBuilder('s')
                            ->select('s._uid');

                    $qOR = $subquery->expr()->orX();
                    if (true === $recursive) {
                        foreach ($nodes as $node) {
                            $qAND = $subquery->expr()->andX();
                            $qAND->add($subquery->expr()->eq('s._root', $subquery->expr()->literal($node->getSection()->getRoot()->getUid())));
                            $qAND->add($subquery->expr()->between('s._leftnode', $node->getLeftnode(), $node->getRightnode()));
                            $qOR->add($qAND);
                        }
                    } else {
                        foreach ($nodes as $node) {
                            $qOR->add($subquery->expr()->eq('s._parent', $subquery->expr()->literal($node->getSection()->getUid())));
                        }
                    }

                    $subquery->andWhere($qOR);

                    $query = 'SELECT c.uid FROM page p LEFT JOIN content c ON c.node_uid = p.uid';
                    $where[] = 'p.section_uid IN ('.$subquery->getQuery()->getSQL().')';

                    if (true === $limitToOnline) {
                        $where[] = 'p.state IN (1, 3)';
                        $where[] = '(p.publishing IS NULL OR p.publishing <= "'.date('Y-m-d H:i:00', time()).'")';
                        $where[] = '(p.archiving IS NULL OR p.archiving >"'.date('Y-m-d H:i:00', time()).'")';
                    } else {
                        $where[] = 'p.state < 4';
                    }

                    $hasPageJoined = true;
                }
            }
        }

        if (0 === count($orderby)) {
            if (true === property_exists('BackBee\CoreDomain\ClassContent\AbstractClassContent', '_'.$selector['orderby'][0])) {
                $orderby[] = 'c.'.$selector['orderby'][0].' '.(count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
            } else {
                $join[] = 'LEFT JOIN indexation isort ON c.uid  = isort.content_uid';
                $where[] = 'isort.field = "'.$selector['orderby'][0].'"';
                $orderby[] = 'isort.value'.' '.(count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
            }
        }

        if (0 < count($join)) {
            $query .= ' '.implode(' ', $join);
        }

        if (0 < count($where)) {
            $query .= sprintf(' WHERE %s', implode(' AND ', $where));
        }

        //Optimize multipage query
        if (true === $multipage) {
            $query = str_replace('SELECT c.uid', 'SELECT SQL_CALC_FOUND_ROWS c.uid', $query);
            $query = str_replace('USE INDEX(IDX_SELECT_PAGE)', ' ', $query);
        }

        $uids = $this->getEntityManager()
                ->getConnection()
                ->executeQuery(str_replace('JOIN content c', 'JOIN opt_content_modified c', $query).' ORDER BY '.implode(', ', $orderby).' LIMIT '.$limit.' OFFSET '.$offset)
                ->fetchAll(\PDO::FETCH_COLUMN);

        if (count($uids) < $limit) {
            $uids = $this->getEntityManager()
                    ->getConnection()
                    ->executeQuery($query.' ORDER BY '.implode(', ', $orderby).' LIMIT '.$limit.' OFFSET '.$offset)
                    ->fetchAll(\PDO::FETCH_COLUMN);
        }

        $q = $this->createQueryBuilder('c')
                ->select()
                ->where('c._uid IN (:uids)')
                ->setParameter('uids', $uids);

        if (true === $hasPageJoined && true === property_exists('BackBee\CoreDomain\NestedNode\Page', '_'.$selector['orderby'][0])) {
            $q->join('c._mainnode', 'p')
                    ->orderBy('p._'.$selector['orderby'][0], count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
        } elseif (true === property_exists('BackBee\CoreDomain\ClassContent\AbstractClassContent', '_'.$selector['orderby'][0])) {
            $q->orderBy('c._'.$selector['orderby'][0], count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
        } else {
            $q->leftJoin('c._indexation', 'isort')
                    ->andWhere('isort._field = :sort')
                    ->setParameter('sort', $selector['orderby'][0])
                    ->orderBy('isort._value', count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
        }

        if (true === $multipage) {
            $numResults = $this->getEntityManager()->getConnection()->executeQuery('SELECT FOUND_ROWS()')->fetch(\PDO::FETCH_COLUMN);
            $result = $q->getQuery()->getResult();

            $q->setFirstResult($offset)
                    ->setMaxResults($limit);

            $paginator = new SettablePaginator($q);
            $paginator
                ->setCount($numResults)
                ->setResult($result)
            ;

            return $paginator;
        }

        $result = $q->getQuery()->getResult();

        return $result;
    }

    /**
     * Returns a set of content by classname.
     *
     * @param array $classnameArr
     * @param array $orderInfos
     * @param array $limitInfos
     *
     * @return array
     */
    public function findContentsByClassname($classnameArr = array(), $orderInfos = array(), $limitInfos = array())
    {
        $result = array();
        if (!is_array($classnameArr)) {
            return $result;
        }
        $db = $this->_em->getConnection();

        $start = (is_array($limitInfos) && array_key_exists('start', $limitInfos)) ? (int) $limitInfos['start'] : 0;
        $limit = (is_array($limitInfos) && array_key_exists('limit', $limitInfos)) ? (int) $limitInfos['limit'] : 0;
        $stmt = $db->executeQuery('SELECT * FROM `content` WHERE `classname` IN (?) order by modified desc limit ?,?', array($classnameArr, $start, $limit), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, 1, 1)
        );

        $items = $stmt->fetchAll();
        if ($items) {
            foreach ($items as $item) {
                $content = $this->_em->find(AbstractClassContent::getFullClassname($item['classname']), $item['uid']);
                if ($content) {
                    $result[] = $content;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the hydrated content by its uid.
     *
     * @param string $uid
     *
     * @return array|null the content if found
     */
    public function findContentByUid($uid)
    {
        $results = $this->findContentsByUids(array($uid));
        if (0 < count($results)) {
            return reset($results);
        }

        return;
    }

    /**
     * Return the classnames from content uids.
     *
     * @param array $uids An array of content uids
     *
     * @return array An array of the classnames
     */
    private function getDistinctClassnamesFromUids(array $uids)
    {
        $classnames = array();

        try {
            if (0 < count($uids)) {
                // Data protection
                array_walk($uids, function (&$item) {
                            $item = addslashes($item);
                });

                // Getting classnames for provided uids
                $classnames = $this->_em
                        ->getConnection()
                        ->createQueryBuilder()
                        ->select('classname')
                        ->from('content', 'c')
                        ->andWhere("uid IN ('".implode("','", $uids)."')")
                        ->execute()
                        ->fetchAll(\PDO::FETCH_COLUMN);
            }
        } catch (\Exception $e) {
            // Ignoring error
        }

        return array_unique($classnames);
    }

    /**
     * Returns the hydrated contents from their uids.
     *
     * @param array $uids
     *
     * @return array An array of AbstractClassContent
     */
    public function findContentsByUids(array $uids)
    {
        $result = array();
        try {
            if (0 < count($uids)) {
                // Getting classnames for provided uids
                $classnames = $this->getDistinctClassnamesFromUids($uids);

                // Construct the DQL query
                $query = $this->createQueryBuilder('c');
                foreach (array_unique($classnames) as $classname) {
                    $query = $query->orWhere('c INSTANCE OF '.$classname);
                }
                $query = $query->andWhere('c._uid IN (:uids)')
                        ->setParameter('uids', $uids);

                $result = $query->getQuery()->execute();
            }
        } catch (\Exception $e) {
            // Ignoring error
        }

        return $result;
    }

    /**
     * sql query example: select c.label, c.classname  FROM content c LEFT JOIN content_has_subcontent cs ON c.uid = cs.content_uid
     * where cs.parent_uid in (select cs.content_uid from page p LEFT JOIN content_has_subcontent cs ON p.contentset = cs.parent_uid
     *       where p.uid = '0007579e1888f8c2a7a0b74c615aa501'
     * );.
     *
     *
     * SELECT c.uid, c.label, c.classname
     * FROM content_has_subcontent cs
     * INNER JOIN content_has_subcontent cs1 ON cs1.parent_uid  = cs.content_uid
     * left join content c on  cs1.content_uid = c.uid
     * left join page p on p.contentset = cs.parent_uid
     * Where p.uid="f70d5b294dcc4d8d5c7f57b8804f4de2"
     *
     * select content where parent_uid
     *
     * @param array $classnames
     * @param array $orderInfos
     * @param array $paging
     * @param array $cond
     *
     * @return array
     */
    public function findContentsBySearch($classnames = array(), $orderInfos = array(), $paging = array(), $cond = array())
    {
        $qb = new ClassContentQueryBuilder($this->_em);

        $this->addContentBySearchFilters($qb, $classnames, $orderInfos, $cond);
        if (is_array($paging) && count($paging)) {
            if (array_key_exists('start', $paging) && array_key_exists('limit', $paging)) {
                $result = $qb->paginate($paging['start'], $paging['limit']);
            } else {
                $result = $qb->getQuery();
            }
        } else {
            $result = $qb->getQuery();
        }

        return $result;
    }

    /**
     * @param array $classnames
     * @param array $cond
     *
     * @return int
     */
    public function countContentsBySearch($classnames = array(), $cond = array())
    {
        $qb = new ClassContentQueryBuilder($this->_em, $this->_em->getExpressionBuilder()->count('cc'));
        $this->addContentBySearchFilters($qb, $classnames, array(), $cond);
        try {
            $result = $qb->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            return 0;
        }

        return reset($result);
    }

    private function addContentBySearchFilters(ClassContentQueryBuilder $qb, $classnames, $orderInfos, $cond)
    {
        if (array_key_exists('selectedpageField', $cond) && !is_null($cond['selectedpageField']) && !empty($cond['selectedpageField'])) {
            $selectedNode = $this->_em->getRepository('BackBee\CoreDomain\NestedNode\Page')->findOneBy(array('_uid' => $cond['selectedpageField']));
            $qb->addPageFilter($selectedNode);
        }

        if (is_array($classnames) && count($classnames)) {
            $qb->addClassFilter($classnames);
        }

        if (true === array_key_exists('site_uid', $cond)) {
            $qb->addSiteFilter($cond['site_uid']);
        }

        if (array_key_exists('keywords', $cond) && is_array($cond['keywords']) && !empty($cond['keywords'])) {
            $qb->addKeywordsFilter($cond['keywords']);
        }

        /* limit to online */
        $limitToOnline = (array_key_exists('only_online', $cond) && is_bool($cond['only_online'])) ? $cond['only_online'] : true;
        if ($limitToOnline) {
            $qb->limitToOnline();
        }

        /* filter by content id */
        if (array_key_exists('contentIds', $cond) && is_array($cond['contentIds']) && !empty($cond['contentIds'])) {
            $qb->addUidsFilter((array) $cond['contentIds']);
        }

        /* handle order info */
        if (is_array($orderInfos) && array_key_exists('column', $orderInfos)) {
            $orderInfos['column'] = ('_' === $orderInfos['column'][0] ? '' : '_').$orderInfos['column'];
            if (property_exists('BackBee\CoreDomain\ClassContent\AbstractClassContent', $orderInfos['column'])) {
                $qb->orderBy('cc.'.$orderInfos['column'], array_key_exists('direction', $orderInfos) ? $orderInfos['direction'] : 'ASC');
            } else {
                $qb->orderByIndex($orderInfos['column'], array_key_exists('direction', $orderInfos) ? $orderInfos['direction'] : 'ASC');
            }
        }

        /* else try to use indexation */
        $searchField = (isset($cond['searchField'])) ? $cond['searchField'] : null;
        if (null !== $searchField) {
            $qb->andWhere($qb->expr()->like('cc._label', $qb->expr()->literal('%'.$searchField.'%')));
        }

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : null;
        if (null !== $afterPubdateField) {
            $qb->andWhere('cc._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));
        }

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : null;
        if (null !== $beforePubdateField) {
            $qb->andWhere('cc._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));
        }

        /* handle indexed fields */
        if (array_key_exists('indexedFields', $cond) && !empty($cond['indexedFields'])) {
            $this->handleIndexedFields($qb, $cond['indexedFields']);
        }
    }

    /* handle custom search */

    private function handleIndexedFields($qb, $criteria)
    {
        $criteria = (is_array($criteria)) ? $criteria : array();
        /* join indexation */
        if (empty($criteria)) {
            return;
        }
        foreach ($criteria as $criterion) {
            //ajouter test
            if (count($criterion) !== 3) {
                continue;
            }
            $criterion = (object) $criterion;
            $alias = uniqid('i'.rand());
            $qb->leftJoin('cc._indexation', $alias)
                    ->andWhere($alias.'._field = :field'.$alias)
                    ->andWhere($alias.'._value '.$criterion->op.' :value'.$alias)
                    ->setParameter('field'.$alias, $criterion->field)
                    ->setParameter('value'.$alias, $criterion->value);
        }
    }

    /**
     * @param Page  $page
     * @param array $classnames
     *
     * @return AbstractContent
     */
    public function getLastByMainnode(Page $page, $classnames = array())
    {
        try {
            $q = $this->createQueryBuilder('c');

            foreach ($classnames as $classname) {
                $q->orWhere('c INSTANCE OF '.  AbstractClassContent::getFullClassname($classname));
            }

            $q->andWhere('c._mainnode = :node')
                ->orderby('c._modified', 'desc')
                ->setMaxResults(1)
                ->setParameters(array('node' => $page))
            ;

            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            $entity = null;
        }

        return $entity;
    }

    /**
     * @param array $classname
     *
     * @return int
     */
    public function countContentsByClassname($classname = array())
    {
        $result = 0;
        if (!is_array($classname)) {
            return $result;
        }
        $db = $this->_em->getConnection();
        $stmt = $db->executeQuery('SELECT count(*) as total FROM `content` WHERE `classname` IN (?)', array($classname), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY));

        $result = $stmt->fetchColumn();

        return $result;
    }

    /**
     * Do removing content from the content editing form.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     * @param  type                                       $value
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $parent
     *
     * @return type
     *
     * @throws ClassContentException
     */
    public function removeFromPost(AbstractClassContent $content, $value = null, AbstractClassContent $parent = null)
    {
        if (null !== $draft = $content->getDraft()) {
            $draft->setState(Revision::STATE_TO_DELETE);
        }

        return $content;
    }

    /**
     * Set the storage directories define by the BB5 application.
     *
     * @param \BackBee\BBApplication $application
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\Element\fileRepository
     */
    public function setDirectories(BBApplication $application = null)
    {
        return $this;
    }

    /**
     * Set the temporary directory.
     *
     * @param type $temporaryDir
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\Element\fileRepository
     */
    public function setTemporaryDir($temporaryDir = null)
    {
        return $this;
    }

    /**
     * Set the storage directory.
     *
     * @param type $storageDir
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\Element\fileRepository
     */
    public function setStorageDir($storageDir = null)
    {
        return $this;
    }

    /**
     * Set the media library directory.
     *
     * @param type $mediaDir
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\Element\fileRepository
     */
    public function setMediaDir($mediaDir = null)
    {
        return $this;
    }

    /**
     * Load content if need, the user's revision is also set.
     *
     * @param  AbstractClassContent $content
     * @param  BBUserToken          $token
     * @param  boolean              $checkoutOnMissing If true, checks out a new revision if none was found
     * @return AbstractClassContent
     */
    public function load(AbstractClassContent $content, BBUserToken $token = null, $checkoutOnMissing = false)
    {
        $revision = null;
        if (null !== $token) {
            $revision = $this->_em->getRepository('BackBee\CoreDomain\ClassContent\Revision')->getDraft(
                $content,
                $token,
                $checkoutOnMissing
            );
        }

        if (false === $content->isLoaded()) {
            $classname = ClassUtils::getRealClass($content);
            if (null !== $refresh = $this->_em->find($classname, $content->getUid())) {
                $content = $refresh;
            }
        }

        $content->setDraft($revision);

        return $content;
    }

    /**
     * Returns the unordered children uids for $content.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     * @return array
     */
    public function getUnorderedChildrenUids(AbstractClassContent $content)
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery('SELECT content_uid FROM content_has_subcontent WHERE parent_uid=?', array($content->getUid()))
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Find pages by Class content
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     *
     * @return Collection<Page>
     */
    public function findPagesByContent($content)
    {
        $contentUids = array_merge($this->getContentsParentUids($content->getUid()), [$content->getUid()]);
        $contentUids = implode(', ', array_unique(array_map(function ($uid) {
            return '"'.$uid.'"';
        }, $contentUids)));

        $pageUids = $this->_em->getConnection()->executeQuery(
            sprintf('SELECT uid FROM page WHERE contentset IN (%s)', $contentUids)
        )->fetchAll(\PDO::FETCH_COLUMN);

        return $this->_em->createQueryBuilder('p')
            ->select('p')
            ->from('BackBee\CoreDomain\NestedNode\Page', 'p')
            ->where('p._uid IN (:uids)')
            ->setParameter('uids', $pageUids)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns all parents uid of provided contents.
     *
     * @param  string|array $contentUids Contents' uid we want to get all parents uid
     * @return array
     */
    public function getContentsParentUids($contentUids)
    {
        $contentUids = (array) $contentUids;
        if (0 === count($contentUids)) {
            return [];
        }

        $contentUids = implode(', ', array_unique(array_map(function ($uid) {
            return '"'.$uid.'"';
        }, $contentUids)));

        $parentUids = $this->_em->getConnection()->executeQuery(
            sprintf('SELECT parent_uid FROM content_has_subcontent WHERE content_uid IN (%s)', $contentUids)
        )->fetchAll(\PDO::FETCH_COLUMN);

        return array_merge($parentUids, $this->getContentsParentUids($parentUids));
    }

    /**
     * Returns an uid if parent with this classname found, false otherwise.
     *
     * @param string $childUid
     * @param string $classname
     *
     * @return mixed
     */
    public function getParentByClassName($childUid, $classname)
    {
        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('j.parent_uid, c.classname')
                ->from('content_has_subcontent', 'j')
                ->from('content', 'c')
                ->andWhere('c.uid = j.parent_uid')
                ->andWhere('j.content_uid = :uid')
                ->setParameter('uid', $childUid);

        $result = $q->execute()->fetch();
        if (false !== $result) {
            if ($result['classname'] == $classname) {
                return $this->_em->find($classname, $result['parent_uid']);
            } else {
                $result = $this->getParentByClassName($result['parent_uid'], $classname);
            }
        } else {
            return;
        }

        return $result;
    }

    /**
     * Delete a Class Content
     *
     * @param AbstractClassContent $content
     * @param bool                 $mainContent if it's a main content, delete recursively under contents
     *
     * @return
     */
    public function deleteContent(AbstractClassContent $content, $mainContent = true)
    {
        $parents = $this->getParentContents($content);
        $media = $this->_em->getRepository('BackBee\NestedNode\Media')->findOneBy([
            '_content' => $content->getUid(),
        ]);

        if ((1 >= count($parents) && null === $media) || true === $mainContent) {
            foreach ($content->getData() as $element) {
                if ($element instanceof AbstractClassContent) {
                    $this->deleteContent($element, false);
                }
            }

            if ($content instanceof ContentSet) {
                $content->clear();
            }

            foreach ($parents as $parent) {
                if (
                    true === $mainContent
                    && !($parent instanceof ContentSet)
                    && !$this->_em->getUnitOfWork()->isScheduledForDelete($parent)
                ) {
                    foreach ($parent->getData() as $key => $element) {
                        if ($element instanceof AbstractClassContent && $element === $content) {
                            $classname = get_class($element);
                            $newContent = new $classname();
                            $this->_em->persist($newContent);
                            $parent->$key = $newContent;
                        }
                    }
                } else {
                    $parent->unsetSubContent($content);
                }
            }

            $this->cleanUpContentHardDelete($content);
            $this->_em->remove($content);
        }
    }

    /**
     * Performs custom process on hard delete of content.
     *
     * @param  AbstractClassContent $content
     * @return self
     */
    protected function cleanUpContentHardDelete(AbstractClassContent $content)
    {
        $this->_em->getConnection()->executeQuery(
            'DELETE FROM indexation WHERE owner_uid = :uid',
            [
                'uid' => $content->getUid(),
            ]
        )->execute();

        $this->_em->getConnection()->executeQuery(
            'DELETE FROM revision WHERE content_uid = :uid',
            [
                'uid' => $content->getUid(),
            ]
        )->execute();

        return $this;
    }

    /**
     * Returns distinct classnames for provided classcontent uids.
     *
     * @param  array $contentUids The array that contains every classcontent uids
     * @return array
     */
    public function getClassnames(array $contentUids)
    {
        $contentUids = array_map(function ($uid) {
            return '"'.$uid.'"';
        }, array_filter($contentUids));

        if (0 === count($contentUids)) {
            return [];
        }

        $qb = $this->_em->getConnection()->createQueryBuilder();

        return $qb
            ->select('DISTINCT c.classname')
            ->from('content', 'c')
            ->where($qb->expr()->in('c.uid', $contentUids))
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN)
        ;
    }

    /**
     * Returns classcontent if couple (type;uid) is valid.
     *
     * @param string $type short namespace of a classcontent
     *                     (full: BackBee\ClassContent\Block\paragraph => short: Block/paragraph)
     * @param string $uid
     *
     * @return AbstractClassContent
     */
    public function findOneByTypeAndUid($type, $uid)
    {
        $content = null;
        $classname = AbstractClassContent::getClassnameByContentType($type);

        if (null === $content = $this->findOneBy(['_uid' => $uid])) {
            throw new \InvalidArgumentException(sprintf('No `%s` exists with uid `%s`.', $classname, $uid));
        }

        return $content;
    }
}
