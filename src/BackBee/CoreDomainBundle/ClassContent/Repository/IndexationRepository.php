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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\ClassContent\Indexation;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\Site\Site;
use BackBee\Util\Doctrine\DriverFeatures;

/**
 * The Indexation repository provides methods to update and access to the
 * indexation content datas stored in the tables:
 *     - indexation: indexed scalar values for a content
 *     - idx_content_content: closure table between content and its sub-contents
 *     - idx_page_content: join table between a page and its contents
 *     - idx_site_content: join table between a site and its contents.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class IndexationRepository extends EntityRepository
{
    /**
     * Is REPLACE command is supported?
     *
     * @var boolean
     */
    private $replaceSupported;

    /**
     * Is multi values insertions command is supported?
     *
     * @var boolean
     */
    private $multiValuesSupported;

    /**
     * Initializes a new EntityRepository.
     *
     * @param \Doctrine\ORM\EntityManager         $em    The EntityManager to use.
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class The class descriptor.
     */
    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->replaceSupported = DriverFeatures::replaceSupported($em->getConnection()->getDriver());
        $this->multiValuesSupported = DriverFeatures::multiValuesSupported($em->getConnection()->getDriver());
    }

    /**
     * Replaces content in optimized tables.
     *
     * @param  AbstractClassContent $content
     *
     * @return IndexationRepository
     */
    public function replaceOptContentTable(AbstractClassContent $content)
    {
        if (null === $content->getMainNode()) {
            return $this;
        }

        $command = 'REPLACE';
        if (!$this->replaceSupported) {
            // REPLACE command not supported, remove first then insert
            $this->removeOptContentTable($content);
            $command = 'INSERT';
        }

        $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\OptContentByModified');
        $query = $command.' INTO '.$meta->getTableName().
                ' ('.$meta->getColumnName('_uid').', '.
                $meta->getColumnName('_label').', '.
                $meta->getColumnName('_classname').', '.
                $meta->getColumnName('_node_uid').', '.
                $meta->getColumnName('_modified').', '.
                $meta->getColumnName('_created').')'.
                ' VALUES (:uid, :label, :classname, :node_uid, :modified, :created)';

        $params = array(
            'uid' => $content->getUid(),
            'label' => $content->getLabel(),
            'classname' => AbstractClassContent::getShortClassname($content),
            'node_uid' => $content->getMainNode()->getUid(),
            'modified' => date('Y-m-d H:i:s', $content->getModified()->getTimestamp()),
            'created' => date('Y-m-d H:i:s', $content->getCreated()->getTimestamp()),
        );

        $types = array(
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
        );

        return $this->_executeQuery($query, $params, $types);
    }

    /**
     * Removes content from optimized table.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function removeOptContentTable(AbstractClassContent $content)
    {
        $this->getEntityManager()
                ->createQuery('DELETE FROM BackBee\ClassContent\Indexes\OptContentByModified o WHERE o._uid=:uid')
                ->setParameter('uid', $content->getUid())
                ->execute();

        return $this;
    }

    /**
     * Replaces site-content indexes for an array of contents in a site.
     *
     * @param \BackBee\CoreDomain\Site\Site $site
     * @param array              $contents An array of AbstractClassContent
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function replaceIdxSiteContents(Site $site, array $contents)
    {
        $contentUids = $this->getAClassContentUids($contents);

        return $this->replaceIdxSiteContentsUid($site->getUid(), $contentUids);
    }

    /**
     * Removes site-content indexes for an array of contents in a site.
     *
     * @param \BackBee\CoreDomain\Site\Site $site
     * @param array              $contents An array of AbstractClassContent
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxSiteContents(Site $site, array $contents)
    {
        return $this->_removeIdxSiteContents($site->getUid(), $this->getAClassContentUids($contents));
    }

    /**
     * Replaces content-content indexes for an array of contents.
     *
     * @param array $contents An array of AbstractClassContent
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function replaceIdxContentContents(array $contents)
    {
        $parentUids = array();
        foreach ($contents as $content) {
            // avoid loop if content is already treated
            if (null === $content || true === $content->isElementContent()) {
                continue;
            } elseif (true === array_key_exists($content->getUid(), $parentUids)) {
                break;
            } elseif (false === array_key_exists($content->getUid(), $parentUids)) {
                $parentUids[$content->getUid()] = array($content->getUid());
            }

            $parentUids[$content->getUid()] = array_merge($parentUids[$content->getUid()], $this->getAClassContentUids($content->getSubcontent()->toArray()));
        }

        return $this->_replaceIdxContentContents($parentUids);
    }

    /**
     * Removes content-content indexes for an array of contents.
     *
     * @param array $contents An array of AbstractClassContent
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxContentContents(array $contents)
    {
        return $this->_removeIdxContentContents($this->getAClassContentUids($contents));
    }

    /**
     * Replaces or inserts a set of Site-Content indexes.
     *
     * @param string $siteUid
     * @param array  $contentUids
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function replaceIdxSiteContentsUid($siteUid, array $contentUids)
    {
        if (0 < count($contentUids)) {
            $command = 'REPLACE';
            if (!$this->replaceSupported) {
                // REPLACE command not supported, remove first then insert
                $this->_removeIdxSiteContents($siteUid, $contentUids);
                $command = 'INSERT';
            }

            $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxSiteContent');

            if (!$this->multiValuesSupported) {
                foreach ($contentUids as $contentUid) {
                    $query = $command . ' INTO ' . $meta->getTableName() .
                            ' (' . $meta->getColumnName('site_uid') . ', ' . $meta->getColumnName('content_uid') . ')' .
                            ' VALUES ("' . $siteUid . '", "' . $contentUid . '")';

                    $this->_em->getConnection()->executeQuery($query);
                }
            } else {
                $query = $command . ' INTO ' . $meta->getTableName() .
                        ' (' . $meta->getColumnName('site_uid') . ', ' . $meta->getColumnName('content_uid') . ')' .
                        ' VALUES ("' . $siteUid . '", "' . implode('"), ("' . $siteUid . '", "', $contentUids) . '")';

                $this->_em->getConnection()->executeQuery($query);
            }
        }

        return $this;
    }

    /**
     * Returns an array of content uids owning provided contents.
     *
     * @param array $uids
     *
     * @return array
     */
    public function getParentContentUidsByUids(array $uids)
    {
        $ids = array();

        $query  = 'SELECT j.parent_uid FROM content_has_subcontent j
                   LEFT JOIN content c ON c.uid = j.content_uid
                   WHERE classname != \'BackBee\ClassContent\Element\'';

        $where = array();
        foreach ($uids as $uid) {
            $where[] = $uid;
        }

        if (count($where) > 0) {
            $query .= ' AND j.content_uid  IN ("'.implode('","', $where).'")';
            $parents = $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query)->fetchAll(\PDO::FETCH_COLUMN);
            if ($parents) {
                $ids = array_merge($ids, $parents, $this->getParentContentUidsByUids($parents));
            }
        }

        return array_unique($ids);
    }

    /**
     * Returns an array of content uids owning provided contents.
     *
     * @param array $contents
     *
     * @return array
     */
    public function getParentContentUids(array $contents)
    {
        $elementUids = [];
        $notElementUids = [];
        foreach ($contents as $content) {
            if (!($content instanceof AbstractClassContent)) {
                continue;
            }

            if ($content->isElementContent()) {
                $elementUids[] = $content->getUid();
            } else {
                $notElementUids[] = $content->getUid();
            }
        }

        if (count($elementUids)) {
            $query = $this->_em->getConnection()
                    ->createQueryBuilder()
                    ->select('j.parent_uid')
                    ->from('content_has_subcontent', 'j');

            $notElementUids = array_merge(
                    $notElementUids, 
                    $this->executeQueryIn($query, 'j.content_uid', $elementUids)
            );
        }

        if (count($notElementUids)) {
            $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxContentContent');
            $query = $this->_em->getConnection()
                    ->createQueryBuilder()
                    ->select('c.'.$meta->getColumnName('content_uid'))
                    ->from($meta->getTableName(), 'c');

            return $this->executeQueryIn($query, 'c.'.$meta->getColumnName('subcontent_uid'), $notElementUids);
        }

        return [];
    }

    /**
     * @param  QueryBuilder $query
     * @param  string       $field
     * @param  string[]     $values
     * 
     * @return string[]
     */
    private function executeQueryIn(QueryBuilder $query, $field, array $values)
    {
        array_walk(
                $values,
                function(&$value, $key, $query) {
                    $value = $query->expr()->literal($value);
                },
                $query
        );

        return $query->where($query->expr()->in($field, $values))
                        ->execute()
                        ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns an array of content uids owned by provided contents.
     *
     * @param mixed $contents
     *
     * @return array
     */
    public function getDescendantsContentUids($contents)
    {
        if (false === is_array($contents)) {
            $contents = array($contents);
        }

        $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxContentContent');

        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.'.$meta->getColumnName('subcontent_uid'))
                ->from($meta->getTableName(), 'c');

        $index = 0;
        $atleastone = false;
        foreach ($contents as $content) {
            if (false === ($content instanceof AbstractClassContent)) {
                continue;
            }

            if (true === $content->isElementContent()) {
                continue;
            }

            $q->orWhere('c.'.$meta->getColumnName('content_uid').' = :uid'.$index)
                    ->setParameter('uid'.$index, $content->getUid());

            $index++;
            $atleastone = true;
        }

        return (true === $atleastone) ? array_unique($q->execute()->fetchAll(\PDO::FETCH_COLUMN)) : array();
    }

    /**
     * Returns every main node attach to the provided content uids.
     *
     * @param array $contentUids
     *
     * @return array
     */
    public function getNodeUids(array $contentUids)
    {
        $meta = $this->_em->getClassMetadata('BackBee\CoreDomain\ClassContent\AbstractClassContent');

        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.node_uid')
                ->from($meta->getTableName(), 'c');

        $q->andWhere('c.'.$meta->getColumnName('_uid').' IN (:ids)')
              ->setParameter('ids', $contentUids);

        return (false === empty($contentUids)) ? array_unique($q->execute()->fetchAll(\PDO::FETCH_COLUMN)) : array();
    }

    /**
     * Removes a set of Site-Content indexes.
     *
     * @param string $siteUid
     * @param array  $contentUids
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxSiteContents($siteUid, array $contentUids)
    {
        if (0 < count($contentUids)) {
            $this->getEntityManager()
                    ->createQuery('DELETE FROM BackBee\ClassContent\Indexes\IdxSiteContent i
                        WHERE i.site_uid=:site_uid
                        AND i.content_uid IN (:content_uids)')
                    ->setParameters(array(
                        'site_uid' => $siteUid,
                        'content_uids' => $contentUids, ))
                    ->execute();
        }

        return $this;
    }

    /**
     * Replaces a set of Site-Content indexes.
     *
     * @param array  $parentUids
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    private function _replaceIdxContentContents(array $parentUids)
    {
        if (0 < count($parentUids)) {
            $command = 'REPLACE';
            if (!$this->replaceSupported) {
                // REPLACE command not supported, remove first then insert
                $this->_removeIdxContentContents(array_keys($parentUids));
                $command = 'INSERT';
            }

            $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxContentContent');
            $insertChildren = array();
            foreach ($parentUids as $parentUid => $subContentUids) {
                foreach ($subContentUids as $subContentUid) {
                    $insertChildren[] = sprintf('SELECT "%s", "%s"', $parentUid, $subContentUid);
                    $insertChildren[] = sprintf('SELECT %s, "%s"
                        FROM %s
                        WHERE %s = "%s"', $meta->getColumnName('content_uid'), $subContentUid, $meta->getTableName(), $meta->getColumnName('subcontent_uid'), $parentUid
                    );
                }
            }

            if (0 < count($insertChildren)) {
                $unionAll = implode(' UNION ALL ', $insertChildren);
                $query = sprintf('%s INTO %s (%s, %s) %s',
                    $command,
                    $meta->getTableName(),
                    $meta->getColumnName('content_uid'),
                    $meta->getColumnName('subcontent_uid'),
                    $unionAll
                );
                $this->_em->getConnection()->executeQuery($query);
            }
        }

        return $this;
    }

    /**
     * Removes a set of Content-Content indexes.
     *
     * @param array $contentUids
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    public function _removeIdxContentContents(array $contentUids)
    {
        if (0 < count($contentUids)) {
            $this->getEntityManager()
                    ->createQuery('DELETE FROM BackBee\ClassContent\Indexes\IdxContentContent i
                        WHERE i.content_uid IN(:content_uids)
                        OR i.subcontent_uid IN(:subcontent_uids)')
                    ->setParameters(array(
                        'content_uids' => $contentUids,
                        'subcontent_uids' => $contentUids, ))
                    ->execute();
        }

        return $this;
    }

    /**
     * Executes an, optionally parameterized, SQL query.
     *
     * @param string $query  The SQL query to execute
     * @param array  $params The parameters to bind to the query, if any
     * @param array  $types  The types the previous parameters are in
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    private function _executeQuery($query, array $params = array(), array $types = array())
    {
        $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query, $params, $types);

        return $this;
    }

    /**
     * Replace site-content indexes for the provided page.
     *
     * @param \BackBee\CoreDomain\NestedNode\Page $page
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    private function _replaceIdxSite(Page $page)
    {
        $query = 'INSERT INTO BackBee\CoreDomain\ClassContent\Indexes\IdxSiteContent (site_uid, content_uid) '.
                '(SELECT :site, content_uid FROM idx_page_content WHERE page_uid = :page)';

        $params = array(
            'page' => $page->getUid(),
            'site' => $page->getSite()->getUid(),
        );

        return $this->removeIdxSite($page)
                        ->_executeQuery($query, $params);
    }

    /**
     * Remove stored content-content indexes from a content.
     *
     * @param \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxContentContent(AbstractClassContent $content)
    {
        $query = 'DELETE FROM idx_content_content WHERE content_uid = :child OR subcontent_uid = :child';

        $params = array(
            'child' => $content->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Remove stored page-content indexes from a content and a page.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent            $content
     * @param  \BackBee\CoreDomain\NestedNode\Page                              $page
     *
     * @return \BackBee\CoreDomainBundle\ClassContent\Repository\IndexationRepository
     */
    private function removeIdxContentPage(AbstractClassContent $content, Page $page)
    {
        $query = 'DELETE FROM idx_page_content WHERE page_uid = :page '.
                'AND (content_uid IN (SELECT subcontent_uid FROM idx_content_content WHERE content_uid = :content) '.
                'OR content_uid IN (SELECT content_uid FROM idx_content_content WHERE subcontent_uid = :content))';

        $params = array(
            'page' => $page->getUid(),
            'content' => $content->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Remove stored site-content indexes from a site and a page.
     *
     * @param Page $page
     *
     * @return IndexationRepository
     */
    private function removeIdxSite(Page $page)
    {
        $query = 'DELETE FROM BackBee\CoreDomain\ClassContent\Indexes\IdxSiteContent WHERE site_uid = :site AND content_uid IN (SELECT content_uid FROM idx_page_content WHERE page_uid = :page)';

        $params = array(
            'page' => $page->getUid(),
            'site' => $page->getSite()->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Replace content-content indexes for the provided content
     * Also replace page-content indexes if content has a main node.
     *
     * @param AbstractClassContent $content
     *
     * @return IndexationRepository
     */
    public function updateIdxContent(AbstractClassContent $content)
    {
        $query = 'INSERT INTO BackBee\CoreDomain\ClassContent\Indexes\IdxContentContent (content_uid, subcontent_uid) '.
                '(SELECT :child, content_uid FROM content_has_subcontent WHERE parent_uid = :child) '.
                'UNION DISTINCT (SELECT parent_uid, :child FROM content_has_subcontent WHERE content_uid = :child) '.
                'UNION DISTINCT (SELECT i.content_uid, :child FROM idx_content_content i WHERE i.subcontent_uid IN (SELECT parent_uid FROM content_has_subcontent WHERE content_uid = :child)) '.
                'UNION DISTINCT (SELECT :child, i.subcontent_uid FROM idx_content_content i WHERE i.content_uid IN (SELECT content_uid FROM content_has_subcontent WHERE parent_uid = :child)) '.
                'UNION DISTINCT (SELECT :child, :child)';

        $params = array(
            'child' => $content->getUid(),
        );

        return $this->_removeIdxContentContent($content)
                        ->_executeQuery($query, $params)
                        ->updateIdxPage($content->getMainNode(), $content);
    }

    /**
     * Replace page-content indexes for the provided page
     * Then replace site_content indexes.
     *
     * @param  Page                 $page
     * @param  AbstractClassContent $content
     *
     * @return IndexationRepository
     */
    public function updateIdxPage(Page $page = null, AbstractClassContent $content = null)
    {
        if (null === $page) {
            return $this;
        }

        if (null === $content) {
            $content = $page->getContentSet();
        }

        $query = 'INSERT INTO BackBee\CoreDomain\ClassContent\Indexes\IdxPageContent (page_uid, content_uid) '.
                '(SELECT :page, subcontent_uid FROM idx_content_content WHERE content_uid = :content) '.
                'UNION DISTINCT (SELECT :page, content_uid FROM idx_content_content WHERE subcontent_uid = :content)';

        $params = array(
            'page' => $page->getUid(),
            'content' => $content->getUid(),
        );

        return $this->removeIdxContentPage($content, $page)
                        ->_executeQuery($query, $params)
                        ->_replaceIdxSite($page);
    }

    /**
     * Replaces site-content indexes for a content in a site.
     *
     * @param  Site                 $site
     * @param  AbstractClassContent $content
     *
     * @return IndexationRepository
     */
    public function updateIdxSiteContent(Site $site, AbstractClassContent $content)
    {
        $query = 'INSERT INTO BackBee\CoreDomain\ClassContent\Indexes\IdxSiteContent (site_uid, content_uid) '.
                '(SELECT :site, content_uid FROM content_has_subcontent WHERE parent_uid = :content)'.
                'UNION '.
                '(SELECT :site, :content) ';

        $params = array(
            'site' => $site->getUid(),
            'content' => $content->getUid(),
        );

        return $this->removeIdxSiteContent($site, $content)
                        ->_executeQuery($query, $params);
    }

    /**
     * Returns an array of AbstractClassContent uids.
     *
     * @param array $contents An array of object
     *
     * @return array
     */
    private function getAClassContentUids(array $contents)
    {
        $contentUids = array();
        foreach ($contents as $content) {
            if ($content instanceof AbstractClassContent &&
                    false === $content->isElementContent()) {
                $contentUids[] = $content->getUid();
            }
        }

        return $contentUids;
    }

    /**
     * Removes all stored indexes for the content.
     *
     * @param AbstractClassContent $content
     *
     * @return IndexationRepository
     */
    public function removeIdxContent(AbstractClassContent $content)
    {
        $params = array(
            'content' => $content->getUid(),
        );

        return $this->_executeQuery('DELETE FROM BackBee\CoreDomain\ClassContent\Indexes\IdxSiteContent WHERE content_uid = :content', $params)
                        ->_executeQuery('DELETE FROM idx_page_content WHERE content_uid = :content', $params)
                        ->_removeIdxContentContent($content);
    }

    /**
     * Remove stored page-content and site-content indexes from a page.
     *
     * @param Page $page
     *
     * @return IndexationRepository
     */
    public function removeIdxPage(Page $page)
    {
        $query = 'DELETE FROM idx_page_content WHERE page_uid = :page';

        $params = array(
            'page' => $page->getUid(),
        );

        return $this->removeIdxSite($page)
                        ->_executeQuery($query, $params);
    }

    /**
     * Removes stored site-content indexes for a content in a site.
     *
     * @param  Site                 $site
     * @param  AbstractClassContent $content
     *
     * @return IndexationRepository
     */
    public function removeIdxSiteContent(Site $site, AbstractClassContent $content)
    {
        $query = 'DELETE FROM BackBee\CoreDomain\ClassContent\Indexes\IdxSiteContent WHERE site_uid = :site AND (content_uid IN '.
                '(SELECT content_uid FROM content_has_subcontent WHERE parent_uid = :content)'.
                'OR content_uid = :content)';

        $params = array(
            'site' => $site->getUid(),
            'content' => $content->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Saves an indexation entity, persists it first if need.
     * 
     * @param Indexation $index The indexation entity to save.
     */
    public function save(Indexation $index)
    {
        $existing = $this->find([
            '_content' => $index->getContent(),
            '_field' => $index->getField(),
        ]);

        if (null === $existing) {
            $existing = $index;
            $this->getEntityManager()->persist($existing);
        } else {
            $existing->setValue($index->getValue());
        }

        $metadata = $this->getEntityManager()->getClassMetadata('BackBee\CoreDomain\ClassContent\Indexation ');
        $this->getEntityManager()
                ->getUnitOfWork()
                ->computeChangeSet($metadata, $existing);
    }
}
