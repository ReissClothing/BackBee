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

namespace BackBee\CoreDomain\NestedNode\Builder;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\Site\Layout;
use BackBee\CoreDomain\Site\Site;

use Doctrine\ORM\EntityManager;

/**
 * Builder utilty class for Page
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PageBuilder
{
    /**
     * Page will not be persisted
     */
    const NO_PERSIST = 0;

    /**
     * Page will be persister as first child of its parent
     */
    const PERSIST_AS_FIRST_CHILD = 1;

    /**
     * Page will be persister as last child of its parent
     */
    const PERSIST_AS_LAST_CHILD = 2;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $uid;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $redirect;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $altTitle;

    /**
     * @var \BackBee\CoreDomain\Site\Site
     */
    private $site;

    /**
     * @var \BackBee\CoreDomain\NestedNode\Page
     */
    private $root;

    /**
     * @var \BackBee\CoreDomain\NestedNode\Page
     */
    private $parent;

    /**
     * @var \BackBee\CoreDomain\Site\Layout
     */
    private $layout;

    /**
     * @var \BackBee\CoreDomain\ClassContent\AbstractClassContent
     */
    private $itemToPushInMainZone;

    /**
     * @var \BackBee\CoreDomain\ClassContent\AbstractClassContent[]
     */
    private $elements;

    /**
     * @var \DateTime
     */
    private $publishedAt;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $archiving;

    /**
     * @var integer
     */
    private $state;

    /**
     * @var integer
     */
    private $persist;

    /**
     * Is the built page a section?
     *
     * @var boolean
     */
    private $isSection = false;

    /**
     * PageBuilder constructor.
     *
     * @param EntityManager         $em                 The entity manager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        $this->reset();
    }

    /**
     * Returns the built page instance.
     *
     * @return Page                                     The built page.
     */
    public function getPage()
    {
        if (null === $this->site || null === $this->layout || null === $this->title) {
            $this->reset();
            throw new \Exception("Required data missing");
        }

        $page = new Page($this->uid);
        $page->setTitle($this->title);
        $page->setSite($this->site);

        if (null !== $this->parent) {
            $page->setParent($this->insureParentIsSection($this->parent));
        }

        $page->setLayout($this->layout, $this->itemToPushInMainZone);

        if (null !== $this->url) {
            $page->setUrl($this->url);
        }

        if (null !== $this->redirect) {
            $page->setRedirect($this->redirect);
        }

        if (null !== $this->target) {
            $page->setTarget($this->target);
        }

        if (null !== $this->altTitle) {
            $page->setAltTitle($this->altTitle);
        }

        if (null !== $this->state) {
            $page->setState($this->state);
        }

        if (null !== $this->publishedAt) {
            $page->setPublishing($this->publishedAt);
        }

        if (null !== $this->createdAt) {
            $page->setCreated($this->createdAt);
        }

        if (null !== $this->archiving) {
            $page->setArchiving($this->archiving);
        }

        $pageContentSet = $page->getContentSet();
        $this->updateContentRevision($pageContentSet);
        while ($column = $pageContentSet->next()) {
            $this->updateContentRevision($column);
        }

        if (0 < count($this->elements)) {
            foreach ($this->elements as $e) {
                $column = $pageContentSet->item($e['content_set_position']);
                if ($e['set_main_node']) {
                    $e['content']->setMainNode($page);
                }

                $column->push($e['content']);
            }

            $pageContentSet->rewind();
        }

        $this->doPersistIfValid($page);

        $this->reset();

        return $page;
    }

    /**
     * Saves $parent with section if need
     *
     * @param  Page                 $parent
     *
     * @return Page
     */
    private function insureParentIsSection(Page $parent)
    {
        if (!$parent->hasMainSection()) {
            $this->em->getRepository('BackBee\CoreDomain\NestedNode\Page')->saveWithSection($parent);
            $this->em->flush($parent);
        }

        return $parent;
    }

    /**
     * Resets the builder
     *
     * @return PageBuilder                              The reseted builder instance.
     */
    private function reset()
    {
        $this->uid = null;
        $this->title = null;
        $this->altTitle = null;
        $this->redirect = null;
        $this->target = null;
        $this->url = null;
        $this->site = null;
        $this->root = null;
        $this->parent = null;
        $this->layout = null;
        $this->elements = array();
        $this->itemToPushInMainZone = null;
        $this->createdAt = null;
        $this->publishedAt = null;
        $this->archiving = null;
        $this->state = null;
        $this->persist = null;
        $this->isSection = false;

        return $this;
    }

    /**
     * Sets an unique identifier for the page.
     *
     * @param  string               $uid                The unique identifier to be set.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Gets the unique identifier of the page.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Sets the title.
     *
     * @param  string               $title              The title to be set.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the title.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the url.
     *
     * @param  string               $url                The url to be set.
     *
     * @return PageBuilder                              The builder instance.
     */
    public function setUrl($url)
    {
        $this->url = preg_replace('/\/+/', '/', $url);

        return $this;
    }

    /**
     * Gets the url.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the site.
     *
     * @param  Site                 $site               The site to be set.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setSite(Site $site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Gets the Site.
     *
     * @return Site
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Sets the root page.
     *
     * @param Page                  $root               The root page to be set.
     * @param boolean               $isChild            Optional, if true sets also the parent of the page to $root (false by default).
     *
     * @return PageBuilder                              The builder instance.
     */
    public function setRoot(Page $root, $isChild = false)
    {
        $this->root = $root;

        if (true === $isChild) {
            $this->setParent($root);
        }

        return $this;
    }

    /**
     * Gets the root page.
     *
     * @return Page
     * @codeCoverageIgnore
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Is the built page a section?
     *
     * @param  boolean              $isSection
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function isSection($isSection = true)
    {
        $this->isSection = (true === $isSection);

        return $this;
    }

    /**
     * Is the built page will be a section?
     *
     * @return boolean
     * @codeCoverageIgnore
     */
    public function willBeSection()
    {
        return $this->isSection;
    }

    /**
     * Sets the parent of the page.
     *
     * @param  Page                 $parent             The parent page to be set.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setParent(Page $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Gets the parent of the page.
     *
     * @return Page
     * @codeCoverageIgnore
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets a layout instance.
     *
     * @param  Layout                       $layout             The layout to be set.
     * @param  AbstractClassContent|null    $toPushInMainZone   Optional, content be pushed in main zone.
     *
     * @return PageBuilder                                      The builder instance.
     * @codeCoverageIgnore
     */
    public function setLayout(Layout $layout, AbstractClassContent $toPushInMainZone = null)
    {
        $this->layout = $layout;
        $this->itemToPushInMainZone = $toPushInMainZone;

        return $this;
    }

    /**
     * Gets the layout instance.
     *
     * @return Layout
     * @codeCoverageIgnore
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set the state of the page.
     *
     * @param  integer              $state              The state to be set.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Gets the state.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the page online and visible.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function putOnlineAndVisible()
    {
        return $this->setState(Page::STATE_ONLINE);
    }

    /**
     * Set the page online and hidden.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function putOnlineAndHidden()
    {
        return $this->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
    }

    /**
     * Push a content into a page.
     *
     * @param AbstractClassContent  $element            The content to be pushed.
     * @param boolean               $setMainNode        Optional, is the main node to be set? (false by deault).
     * @param integer               $contentSetPos      Optional, the column index in which to push content (0 by default).
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function pushElement(AbstractClassContent $element, $setMainNode = false, $contentSetPos = 0)
    {
        $this->elements[] = [
            'content'               => $element,
            'set_main_node'         => (true === $setMainNode),
            'content_set_position'  => $contentSetPos,
        ];

        return $this;
    }

    /**
     * Add a content into a page.
     *
     * @param AbstractClassContent  $element            The content to be add.
     * @param integer|null          $index              Optional, if provided replace content at index $index.
     * @param boolean               $setMainNode        Optional, is the main node to be set? (false by deault).
     * @param integer               $contentSetPos      Optional, the column index in which to push content (0 by default).
     *
     * @return PageBuilder                              The builder instance.
     *
     * @throws \InvalidArgumentException                Raises if $index does not exist.
     */
    public function addElement(AbstractClassContent $element, $index = null, $setMainNode = false, $contentSetPos = 0)
    {
        if (null !== $index) {
            $index = intval($index);
            if (!array_key_exists($index, $this->elements)) {
                throw new \InvalidArgumentException();
            }

            $this->elements[$index] = array(
                'content'               => $element,
                'set_main_node'         => (true === $setMainNode),
                'content_set_position'  => $contentSetPos,
            );
        } else {
            $this->pushElement($element, $setMainNode, $contentSetPos);
        }

        return $this;
    }

    /**
     * Gets a content from the page.
     *
     * @param integer               $index              The index in elements array of the page.
     *
     * @return AbstractClassContent|null
     */
    public function getElement($index)
    {
        return (array_key_exists((int) $index, $this->elements) ? $this->elements[$index] : null);
    }

    /**
     * Gets the contents array.
     *
     * @return AbstractClassContent[]
     * @codeCoverageIgnore
     */
    public function elements()
    {
        return $this->elements;
    }

    /**
     * Empties the contents array.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function clearElements()
    {
        $this->elements = array();

        return $this;
    }

    /**
     * Updates states and revision number of the content.
     *
     * @param AbstractClassContent  $content            The content to be updated.
     * @param integer               $revision           Optional, the revision number (1 by default).
     * @param integer               $state              Optional, The state (STATE_NORMAL by default).
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    private function updateContentRevision(AbstractClassContent $content, $revision = 1, $state = AbstractClassContent::STATE_NORMAL)
    {
        $content->setRevision((int) $revision);
        $content->setState((int) $state);

        return $this;
    }

    /**
     * Gets the value of publishedAt.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Sets the value of publishedAt.
     *
     * @param \DateTime|null        $publishedAt        Optional, the published at.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function publishedAt(\DateTime $publishedAt = null)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Alias of publishedAt.
     *
     * @param \DateTime|null        $publishing         Optional, the published at.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setPublishing(\DateTime $publishing = null)
    {
        return $this->publishedAt($publishing);
    }

    /**
     * Gets the value of createdAt.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the value of createdAt.
     *
     * @param \DateTime             $createdAt          The created at datetime.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function createdAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets the value of archiving.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getArchiving()
    {
        return $this->archiving;
    }

    /**
     * Sets the value of archiving.
     *
     * @param \DateTime|null        $archiving          Optional, the archiving datetime.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setArchiving(\DateTime $archiving = null)
    {
        $this->archiving = $archiving;

        return $this;
    }

    /**
     * Gets the value of target.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Sets the value of target.
     *
     * @param  string|null          $target             Optional, the target.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setTarget($target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Gets the value of redirect.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Sets the value of redirect.
     *
     * @param  string|null          $redirect           Optional, the redirect.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setRedirect($redirect = null)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * Gets the value of altTitle.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAltTitle()
    {
        return $this->altTitle;
    }

    /**
     * Sets the value of the alternative title.
     *
     * @param  string|null          $altTitle           Optional, the alternative title.
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setAltTitle($altTitle = null)
    {
        $this->altTitle = $altTitle;

        return $this;
    }

    /**
     * Sets the persist mode.
     *
     * /!\ if you set a valid persist mode (SELF::INSERT_AS_FIRST_CHILD or SELF::INSERT_AS_LAST_CHILD),
     * this page will be persist for you, it also will modifie the left and right node of the tree.
     *
     * @param integer               $mode               Either SELF::NO_PERSIST, SELF::INSERT_AS_FIRST_CHILD or
     *                                                  SELF::INSERT_AS_LAST_CHILD
     *
     * @return PageBuilder                              The builder instance.
     * @codeCoverageIgnore
     */
    public function setPersistMode($mode)
    {
        $this->persist = $mode;

        return $this;
    }

    /**
     * Persists the page is need and valid
     *
     * @param  Page                 $page               The page to be built.
     */
    private function doPersistIfValid(Page $page)
    {
        if (null === $page->getParent() && !empty($this->persist)) {
            // If root, only persist
            $this->em->persist($page);
            return;
        }

        $method = '';
        if (self::PERSIST_AS_FIRST_CHILD === $this->persist) {
            $method = 'insertNodeAsFirstChildOf';
        } elseif (self::PERSIST_AS_LAST_CHILD === $this->persist) {
            $method = 'insertNodeAsLastChildOf';
        }

        if (!empty($method)) {
            $this->em->getRepository('BackBee\CoreDomain\NestedNode\Page')->$method($page, $page->getParent(), $this->isSection);
            $this->em->persist($page);
        }
    }
}
