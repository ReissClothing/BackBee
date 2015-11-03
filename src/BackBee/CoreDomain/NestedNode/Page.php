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

namespace BackBee\CoreDomain\NestedNode;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\ClassContent\ContentSet;
use BackBee\Exception\InvalidArgumentException;
//use BackBee\Installer\Annotation as BB;
use BackBee\MetaData\MetaDataBag;
use BackBee\CoreDomain\Renderer\RenderableInterface;
use BackBee\CoreDomain\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Site\Layout;
use BackBee\CoreDomain\Site\Site;
use BackBee\Utils\Numeric;
use BackBee\Workflow\State;

/**
 * Page object in BackBee.
 *
 * A page basically is an URI an a set of content defined for a website.
 * A page must have a layout defined to be displayed.
 *
 * State of a page is bit operation on one or several following values:
 *
 * * STATE_OFFLINE
 * * STATE_ONLINE
 * * STATE_HIDDEN
 * * STATE_DELETED
 *
 * @category    BackBee
 *
 * @IgnoreAnnotation("BB\Fixtures")
 * @IgnoreAnnotation("BB\Fixture")
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\NestedNode\Repository\PageRepository")
 * @ORM\Table(name="page",indexes={
 *     @ORM\Index(name="IDX_STATE_PAGE", columns={"state"}),
 *     @ORM\Index(name="IDX_SELECT_PAGE", columns={"level", "state", "publishing", "archiving", "modified"}),
 *     @ORM\Index(name="IDX_URL", columns={"url"}),
 *     @ORM\Index(name="IDX_MODIFIED_PAGE", columns={"modified"}),
 *     @ORM\Index(name="IDX_ARCHIVING", columns={"archiving"}),
 *     @ORM\Index(name="IDX_PUBLISHING", columns={"publishing"})
 * })
 * @ORM\HasLifecycleCallbacks
 * @BB\Fixtures(qty=1)
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Page extends AbstractObjectIdentifiable implements RenderableInterface, DomainObjectInterface
{
    /**
     * State off-line: the page can not be displayed on the website.
     *
     * @var int
     */
    const STATE_OFFLINE = 0;

    /**
     * State on-line: the page can be displayed on the website.
     *
     * @var int
     */
    const STATE_ONLINE = 1;

    /**
     * State hidden: the page can not appeared in menus.
     *
     * @var int
     */
    const STATE_HIDDEN = 2;

    /**
     * State deleted: the page does not appear in the tree of the website.
     *
     * @var int
     */
    const STATE_DELETED = 4;

    /**
     * Type static: thez page is an stored and managed entity.
     *
     * @var int
     */
    const TYPE_STATIC = 1;

    /**
     * Type dynamic: the page is not a managed entity.
     *
     * @var int
     */
    const TYPE_DYNAMIC = 2;

    /**
     * Default target if redirect is defined.
     *
     * @var string
     */
    const DEFAULT_TARGET = '_self';

    /**
     * Unique identifier of the page.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid", length=32)
     * @BB\Fixtures(type="md5")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     * @Serializer\ReadOnly
     */
    protected $_uid;

    /**
     * The layout associated to the page.
     *
     * @var \BackBee\Site\Layout
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\Site\Layout", inversedBy="_pages", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="layout_uid", referencedColumnName="uid")
     */
    protected $_layout;

    /**
     * The title of this page.
     *
     * @var string
     * @ORM\Column(type="string", name="title", nullable=false, length=255)
     * @BB\Fixtures(type="sentence", value=6)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_title;

    /**
     * The alternate title of this page.
     *
     * @var string
     * @ORM\Column(type="string", name="alttitle", nullable=true, length=255)
     * @BB\Fixtures(type="sentence", value=6)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_alttitle;

    /**
     * The URI of this page.
     *
     * @var string
     * @ORM\Column(type="string", name="url", nullable=false, length=255)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_url;

    /**
     * Target of this page if redirect defined.
     *
     * @var string
     * @ORM\Column(type="string", name="target", nullable=false, length=15)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_target;

    /**
     * Permanent redirect.
     *
     * @var string
     * @ORM\Column(type="string", name="redirect", nullable=true, length=255)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_redirect;

    /**
     * Metadatas associated to the page.
     *
     * @var \BackBee\MetaData\MetaDataBag
     * @ORM\Column(type="object", name="metadata", nullable=true)
     */
    protected $_metadata;

    /**
     * The associated ContentSet.
     *
     * @var \BackBee\CoreDomain\ClassContent\ContentSet
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\ClassContent\ContentSet", inversedBy="_pages", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="contentset", referencedColumnName="uid")
     */
    protected $_contentset;

    /**
     * The publication datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="date", nullable=true)
     * @BB\Fixture(type="dateTime")
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_date;

    /**
     * The state of the page.
     *
     * @var int
     * @ORM\Column(type="smallint", name="state", nullable=false)
     * @BB\Fixture(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    protected $_state;

    /**
     * The auto publishing datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="publishing", nullable=true)
     *
     */
    protected $_publishing;

    /**
     * The auto-archiving datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="archiving", nullable=true)
     *
     */
    protected $_archiving;

    /**
     * The optional workflow state.
     *
     * @var \BackBee\Workflow\State
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\Workflow\State", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="workflow_state", referencedColumnName="uid")
     */
    protected $_workflow_state;

    /**
     * Revisions of the current page.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\NestedNode\PageRevision", mappedBy="_page", fetch="EXTRA_LAZY")
     */
    protected $_revisions;

    /**
     * The nested node level in the tree.
     *
     * @var int
     * @ORM\Column(type="integer", name="level", nullable=false)
     */
    protected $_level;

    /**
     * The order position in the section.
     *
     * @var int
     * @ORM\Column(type="integer", name="position", nullable=false)
     */
    protected $_position;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created", nullable=false)
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     */
    protected $_modified;

    /**
     * The section node.
     *
     * @var \BackBee\CoreDomain\NestedNode\Section
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\NestedNode\Section", inversedBy="_pages", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="section_uid", referencedColumnName="uid")
     */
    protected $_section;

    /**
     * The associated page of this section.
     *
     * @var \BackBee\CoreDomain\NestedNode\Section
     * @ORM\OneToOne(targetEntity="BackBee\CoreDomain\NestedNode\Section", mappedBy="_page", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $_mainsection;

    /**
     * The type of the page, either static (ie persisted) or dynamic.
     *
     * @var int
     */
    protected $_type;

    /**
     * An array of ascendants.
     *
     * @var array
     */
    protected $breadcrumb = null;

    /**
     * Associated array of available states for the page.
     *
     * @var array
     */
    public static $STATES = [
        'Offline' => self::STATE_OFFLINE,
        'Online'  => self::STATE_ONLINE,
        'Hidden'  => self::STATE_HIDDEN,
        'Deleted' => self::STATE_DELETED,
    ];

    /**
     * Utility property used on cloning page.
     *
     * @var array
     */
    public $cloningData;

    /**
     * Whether redirect url should be returned by getUrl() method.
     *
     * @var bool
     */
    private $useUrlRedirect = true;

    /**
     * Class constructor.
     *
     * @param string|null           $uid                The unique identifier of the page.
     * @param array|null            $options            Initial options for the page:
     *                                                              - main_section    the default main section
     *                                                              - title           the default title
     *                                                              - url             the default url
     */
    public function __construct($uid = null, $options = null)
    {
        $defaultOptions = [
            'main_section' => null,
            'title' => null,
            'url' => null
        ];
        $defaultValues = array_merge($defaultOptions, (array) $options);
        $this->setDefaultProperties($uid, $defaultValues['main_section'], $defaultValues['title'], $defaultValues['url']);

        $this->_contentset = new ContentSet();
        $this->_revisions = new ArrayCollection();
    }

    /**
     * Sets the default values to properties.
     *
     * @param  string|null          $uid
     * @param  Section|null         $mainSection
     * @param  string|null          $title
     * @param  string|null          $url
     * @param  string               $target
     *
     * @return Page
     */
    private function setDefaultProperties($uid = null, Section $mainSection = null, $title = null, $url = null, $target = self::DEFAULT_TARGET)
    {
        $this->_state = Page::STATE_HIDDEN;
        $this->_target = $target;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_title = $title;
        $this->_url = $url;
        $this->_type = Page::TYPE_DYNAMIC;

        if (null === $mainSection) {
            $mainSection = new Section($uid, ['page' => $this]);
        }
        $this->setMainSection($mainSection);
        $this->_uid = $mainSection->getUid();

        return $this;
    }

    /**
     * Magical cloning method.
     */
    public function __clone()
    {
        $currentUid = $this->_uid;

        $this->cloningData = [
            'pages' => [],
            'contents' => [],
        ];

        if (null !== $this->_contentset && null !== $this->getLayout()) {
            $this->_contentset = $this->_contentset->createClone($this);
        } else {
            $this->_contentset = new ContentSet();
        }

        if ($this->hasMainSection()) {
            // Main section has to be cloned also
            $this->setDefaultProperties(null, clone $this->_mainsection, $this->_title, null, $this->_target);
        } else {
            // The new page keeps the same section
            $section = $this->getSection();
            $this->setDefaultProperties(null, null, $this->_title, null, $this->_target);
            if (null !== $section) {
                $this->setSection($section);
            }
        }

        $this->_revisions = new ArrayCollection();
        $this->cloningData['pages'][$currentUid] = $this;
    }

    /**
     * Returns the owner site of this node.
     *
     * @return Site
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->getSection()->getSite();
    }

    /**
     * Returns the main contentset associated to the node.
     *
     * @return ContentSet
     */
    public function getContentSet()
    {
        if (null === $this->_contentset) {
            $this->_contentset = new ContentSet();
        }

        return $this->_contentset;
    }

    /**
     * Returns the layout of the page.
     *
     * @return Layout
     * @codeCoverageIgnore
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns the alternate title of the page.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAltTitle()
    {
        return $this->_alttitle;
    }

    /**
     * Returns the title of the page.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns the URL of the page.
     *
     * @param boolean|null          $doRedirect         If true - returns redirect url (if exists), otherwise - current page url.
     *
     * @return string
     */
    public function getUrl($doRedirect = null)
    {
        if (null === $doRedirect) {
            $doRedirect = $this->useUrlRedirect;
        }

        if ($this->isRedirect() && $doRedirect) {
            return $this->getRedirect();
        }

        return $this->_url;
    }

    /**
     * Returns the URL with extension of the page.
     *
     * @return string
     */
    public function getNormalizeUri()
    {
        if (null === $this->getSite()) {
            return $this->getUrl();
        }

        return $this->getUrl().$this->getSite()->getDefaultExtension();
    }

    /**
     * Returns the target.
     *
     * @return string
     */
    public function getTarget()
    {
        return ((null === $this->_target) ? self::DEFAULT_TARGET : $this->_target);
    }

    /**
     * Returns the premanent redirect URL if defined.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getRedirect()
    {
        return $this->_redirect;
    }

    /**
     * Determines if page is a redirect.
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return null !== $this->_redirect;
    }

    /**
     * Returns the associated metadata if defined.
     *
     * @return MetaDataBag
     * @codeCoverageIgnore
     */
    public function getMetaData()
    {
        return $this->_metadata;
    }

    /**
     * Returns the state of the page.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Returns the date.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * Returns the publishing date if defined.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getPublishing()
    {
        return $this->_publishing;
    }

    /**
     * Returns the archiving date if defined.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getArchiving()
    {
        return $this->_archiving;
    }

    /**
     * Returns the collection of revisions.
     *
     * @return ArrayCollection
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided.
     *
     * @param string                $var
     *
     * @return string|array|null
     */
    public function getData($var = null)
    {
        return null !== $var ? null : [];
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided.
     *
     * @param string                $var
     *
     * @return string|array|null
     */
    public function getParam($var = null)
    {
        $param = [
            'left'      => $this->getLeftnode(),
            'right'     => $this->getRightnode(),
            'level'     => $this->getLevel(),
            'position'  => $this->getPosition(),
        ];

        if (null !== $var) {
            if (false === array_key_exists($var, $param)) {
                return;
            }

            return $param[$var];
        }

        return $param;
    }

    /**
     * Returns the worflow state if defined, NULL otherwise.
     *
     * @return State
     * @codeCoverageIgnore
     */
    public function getWorkflowState()
    {
        return $this->_workflow_state;
    }

    /**
     * Returns true if the page can be rendered.
     *
     * @return boolean
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return $this->isOnline();
    }

    /**
     * Is the publishing state of the page is scheduled?
     *
     * @return boolean                                  True if the publishing state is scheduled, false otherwise.
     */
    public function isScheduled()
    {
        return (null !== $this->getPublishing() || null !== $this->getArchiving());
    }

    /**
     * Is the page is visible (ie online and not hidden)?
     *
     * @return boolean                                  True if the page is visible, false otherwise.
     */
    public function isVisible()
    {
        return ($this->isOnline() && !($this->getState() & self::STATE_HIDDEN));
    }

    /**
     * Is the page online?
     *
     * @param  boolean              $ignoreSchedule     Don't take care of publishing period.
     *
     * @return boolean                                  True if the page is online, false otherwise.
     */
    public function isOnline($ignoreSchedule = false)
    {
        $onlineByState = ($this->getState() & self::STATE_ONLINE) && !($this->getState() & self::STATE_DELETED);

        if (true === $ignoreSchedule) {
            return $onlineByState;
        } else {
            return $onlineByState
                && (null === $this->getPublishing() || 0 === $this->getPublishing()->diff(new \DateTime())->invert)
                && (null === $this->getArchiving() || 1 === $this->getArchiving()->diff(new \DateTime())->invert)
            ;
        }
    }

    /**
     * Is the page deleted?
     *
     * @return boolean                                  True if the page has been deleted.
     */
    public function isDeleted()
    {
        return 0 < ($this->getState() & self::STATE_DELETED);
    }

    /**
     * Is the page is static?
     *
     * @return boolean
     * @codeCoverageIgnore
     */
    public function isStatic()
    {
        return (Page::TYPE_STATIC === $this->_type);
    }

    /**
     * Sets the associated site.
     *
     * @param  Site                 $site
     *
     * @return Page
     */
    public function setSite(Site $site = null)
    {
        $this->getSection()->setSite($site);

        return $this;
    }

    /**
     * Sets the main contentset associated to the node.
     *
     * @param  ContentSet           $contentset
     *
     * @return Page
     */
    public function setContentset(ContentSet $contentset)
    {
        $this->_contentset = $contentset;

        return $this;
    }

    /**
     * Sets the date of the page.
     *
     * @param  \DateTime            $date
     *
     * @return Page
     */
    public function setDate(\DateTime $date = null)
    {
        $this->_date = $date;

        return $this;
    }

    /**
     * Sets the layout for the page.
     * Adds as much ContentSet to the page main ContentSet than defined zones in layout.
     *
     * @param  Layout               $layout
     * @param  AbstractClassContent $toPushInMainZone
     *
     * @return Page
     */
    public function setLayout(Layout $layout, AbstractClassContent $toPushInMainZone = null)
    {
        $this->_layout = $layout;

        $count = count($layout->getZones());
        // Add as much ContentSet to the page main ContentSet than defined zones in layout
        for ($i = $this->getContentSet()->count(); $i < $count; $i++) {
            // Do this case really exists ?
            if (null === $zone = $layout->getZone($i)) {
                $this->getContentSet()->push(new ContentSet());
                continue;
            }

            // Create a new column
            $contentset = new ContentSet(null, $zone->options);

            if (null !== $toPushInMainZone && true === $zone->mainZone) {
                // Existing content push in the main zone
                $contentset->push($toPushInMainZone->setMainNode($this));
            } elseif ('inherited' === $zone->defaultClassContent) {
                // Inherited zone => same ContentSet than parent if exist
                $contentset = $this->getInheritedContent($i, $contentset);
            } elseif ($zone->defaultClassContent) {
                // New default content push
                $contentset->push($this->createNewDefaultContent(
                    'BackBee\ClassContent\\'.$zone->defaultClassContent,
                    $zone->mainZone
                ));
            }

            $this->getContentSet()->push($contentset);
        }

        return $this;
    }

    /**
     * Sets the alternate title of the page.
     *
     * @param  string               $alttitle
     *
     * @return Page
     */
    public function setAltTitle($alttitle)
    {
        $this->_alttitle = $alttitle;

        return $this;
    }
    /**
     * Sets the title of the page.
     *
     * @param  string               $title
     *
     * @return Page
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    /**
     * Sets the URL of the page.
     *
     * @param  string               $url
     *
     * @return Page
     */
    public function setUrl($url)
    {
        $this->_url = $url;

        return $this;
    }

    /**
     * Sets the target if a permanent redirect is defined.
     *
     * @param  string               $target
     *
     * @return Page
     */
    public function setTarget($target)
    {
        $this->_target = $target;

        return $this;
    }

    /**
     * Sets a permanent redirect.
     *
     * @param  string               $redirect
     *
     * @return Page
     */
    public function setRedirect($redirect)
    {
        $this->_redirect = $redirect;

        return $this;
    }

    /**
     * Sets the associated metadata.
     *
     * @param  MetaDataBag|null     $metadata
     *
     * @return Page
     */
    public function setMetaData(MetaDataBag $metadata = null)
    {
        $this->_metadata = $metadata;

        return $this;
    }

    /**
     * Sets the state.
     *
     * @param  integer              $state
     *
     * @return Page
     *
     * @throws \LogicException                          Raises if this page is root and not online.
     */
    public function setState($state)
    {
        $state = (int) $state;
        if ($this->isRoot() && !($state & Page::STATE_ONLINE)) {
            throw new \LogicException("Root page state must be online.");
        }

        $this->_state = $state;

        return $this;
    }

    /**
     * Sets the publishing date.
     *
     * @param  \DateTime|null       $publishing
     *
     * @return Page
     */
    public function setPublishing($publishing = null)
    {
        if ($publishing === null) {
            $this->_publishing = null;
        } else {
            $this->_publishing = $this->validatePublishing($publishing);
        }

        return $this;
    }

    /**
     * Validate the publishing date.
     *
     * @param  \DateTime            $publishing
     *
     * @return \DateTime
     *
     * @throws \LogicException                          Raises if this page is root and publishing not null
     *                                                  or page not root but publishing datetime in the past.
     */
    private function validatePublishing($publishing)
    {
        if ($this->isRoot() && $publishing !== null) {
            throw new \LogicException("Root page can't be scheduled published.");
        }

        if (!($publishing instanceof \DateTime)) {
            $publishing = $this->convertTimestampToDateTime($publishing);
        }

        if ($publishing->getTimestamp() < time()) {
            throw new \LogicException("Page can't be published in the past.");
        }

        return $publishing;
    }

    /**
     * Sets the archiving date.
     *
     * @param  \DateTime|null       $archiving
     *
     * @return Page
     */
    public function setArchiving($archiving = null)
    {
        if ($archiving === null) {
            $this->_archiving = null;
        } else {
            $this->_archiving = $this->validateArchiving($archiving);
        }

        return $this;
    }

    /**
     * Validate the archiving date.
     *
     * @param  \DateTime            $archiving
     *
     * @return \DateTime
     *
     * @throws \LogicException                          Raises if this page is root and archiving not null
     *                                                  or page not root but archiving datetime in the past.
     */
    private function validateArchiving($archiving)
    {
        if ($this->isRoot() && $archiving !== null) {
            throw new \LogicException("Root page can't be archived.");
        }

        if (!($archiving instanceof \DateTime)) {
            $archiving = $this->convertTimestampToDateTime($archiving);
        }

        if ($archiving->getTimestamp() < time() || $archiving < $this->_publishing) {
            throw new \LogicException("Page can't be archived in the past or before publication date.");
        }

        return $archiving;
    }

    /**
     * Sets a collection of revisions for the page.
     *
     * @param  ArrayCollection      $revisions
     *
     * @return Page
     */
    public function setRevisions(ArrayCollection $revisions)
    {
        $this->_revision = $revisions;

        return $this;
    }

    /**
     * Sets the workflow state.
     *
     * @param  State|null           $state
     *
     * @return Page
     */
    public function setWorkflowState(State $state = null)
    {
        $this->_workflow_state = $state;

        return $this;
    }

    /**
     * Returns the inherited zone according to the provided ContentSet.
     *
     * @param  ContentSet           $contentSet
     *
     * @return \StdClass|null                           The inherited zone if found.
     */
    public function getInheritedContensetZoneParams(ContentSet $contentSet)
    {
        $zone = null;

        if (
            null === $this->getLayout()
            || null === $this->getParent()
            || false === is_array($this->getLayout()->getZones())
        ) {
            return $zone;
        }

        $layoutZones = $this->getLayout()->getZones();
        $count = $this->getParent()->getContentSet()->count();
        for ($i = 0; $i < $count; $i++) {
            $parentContentset = $this->getParent()->getContentSet()->item($i);

            if ($contentSet->getUid() === $parentContentset->getUid()) {
                $zone = $layoutZones[$i];
            }
        }

        return $zone;
    }

    /**
     * Returns the index of the provided ContentSet in the main ContentSetif found, false otherwise.
     *
     * @param  ContentSet           $contentSet
     *
     * @return integer|false
     */
    public function getRootContentSetPosition(ContentSet $contentSet)
    {
        return $this->getContentSet()->indexOfByUid($contentSet, true);
    }

    /**
     * Returns the parent ContentSet in the same zone, false if it is not found.
     *
     * @param  ContentSet           $contentSet
     *
     * @return ContentSet|false
     */
    public function getParentZoneAtSamePositionIfExists(ContentSet $contentSet)
    {
        $indexOfContent = $this->getContentSet()->indexOfByUid($contentSet, true);
        if (false === $indexOfContent) {
            return false;
        }

        $parent = $this->getParent();
        if (null === $parent) {
            return false;
        }

        $parentContentSet = $parent->getContentSet()->item($indexOfContent);
        if ($parentContentSet) {
            return $parentContentSet;
        }

        return false;
    }

    /**
     * Tells which "rootContentset" is inherited from currentpage's parent.
     *
     * @param boolean               $uidOnly
     *
     * @return array                                    Array of contentset uids
     */
    public function getInheritedZones($uidOnly = false)
    {
        $inheritedZones = array();
        $uidOnly = (isset($uidOnly) && is_bool($uidOnly)) ? $uidOnly : false;
        if (null !== $this->getParent()) {
            $parentZones = $this->getParent()->getContentSet();
            $cPageRootZoneContainer = $this->getContentSet();
            foreach ($cPageRootZoneContainer as $currentpageRootZone) {
                $result = $parentZones->indexOfByUid($currentpageRootZone);
                if ($result) {
                    $inheritedZones[$currentpageRootZone->getUid()] = $currentpageRootZone;
                }
            }
            if ($uidOnly) {
                $inheritedZones = array_keys($inheritedZones);
            }
        }

        return $inheritedZones;
    }

    /**
     * Returns the main zones of the page
     * Page's mainzone can't be unlinked.
     *
     * @return array
     */
    public function getPageMainZones()
    {
        $result = array();

        if (null === $this->getLayout()) {
            return $result;
        }

        $currentpageRootZones = $this->getContentSet();
        $layoutZones = $this->getLayout()->getZones();
        $count = count($layoutZones);
        for ($i = 0; $i < $count; $i++) {
            $zoneInfos = $layoutZones[$i];
            $currentZone = $currentpageRootZones->item($i);

            if (
                null !== $currentZone
                && null !== $zoneInfos
                && true === property_exists($zoneInfos, 'mainZone')
                && true === $zoneInfos->mainZone
            ) {
                $result[$currentZone->getUid()] = $currentZone;
            }
        }

        return $result;
    }

    /**
     * Is the ContentSet is linked to his parent.
     *
     * @param  ContentSet           $contentset
     *
     * @return boolean
     */
    public function isLinkedToHisParentBy(ContentSet $contentset = null)
    {
        if (
            null !== $contentset &&
            true === array_key_exists($contentset->getUid(), $this->getInheritedZones())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Replaces the ContentSet of the page.
     *
     * @param  ContentSet           $contentToReplace
     * @param  ContentSet           $newContentSet
     * @param  boolean              $checkContentsLinkToParent
     *
     * @return ContentSet
     */
    public function replaceRootContentSet(ContentSet $contentToReplace, ContentSet $newContentSet, $checkContentsLinkToParent = true)
    {
        $checkContentsLinkToParent = (true === $checkContentsLinkToParent);
        $contentIsLinked = true === $checkContentsLinkToParent ? $this->isLinkedToHisParentBy($contentToReplace) : true;

        if (true === $contentIsLinked) {
            if (null !== $this->getContentSet()) {
                $this->getContentSet()->replaceChildBy($contentToReplace, $newContentSet);
            }
        }

        return $newContentSet;
    }

    /**
     * Returns states except deleted.
     *
     * @return integer[]
     * @codeCoverageIgnore
     */
    public static function getUndeletedStates()
    {
        return array(
            Page::STATE_OFFLINE,
            Page::STATE_ONLINE,
            Page::STATE_HIDDEN,
            Page::STATE_ONLINE + Page::STATE_HIDDEN,
        );
    }

    /**
     * Returns chidren of the page.
     *
     * @return ArrayCollection
     *
     * @deprecated
     */
    public function getChildren()
    {
        if (false === $this->hasMainSection()) {
            return array();
        }
        return $this->getSection()->getPages();
    }

    /**
     * Looks for at least one online children.
     *
     * @return boolean                                  True if at least one children of the page is online.
     *
     * @deprecated
     */
    public function hasChildrenVisible()
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getState() == static::STATE_ONLINE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of the ascendants.
     *
     * @param  Page                 $page
     * @param  array                $breadcrumb
     *
     * @return Page[]
     *
     * @deprecated
     */
    public function getBreadcrumb(Page $page = null, $breadcrumb = array())
    {
        if (null === $this->breadcrumb) {
            $page = (null !== $page) ? $page : $this;
            $breadcrumb[] = $page;
            if (null !== $page->getParent()) {
                return $this->getBreadcrumb($page->getParent(), $breadcrumb);
            } else {
                $this->breadcrumb = $breadcrumb;
            }
        }

        return $this->breadcrumb;
    }

    /**
     * Returns an array of the unique identifiers of the ascendants.
     *
     * @return string[]
     *
     * @deprecated
     */
    public function getBreadcrumb_uids()
    {
        $breadcrumb_uids = array();
        foreach ($this->getBreadcrumb() as $page) {
            $breadcrumb_uids[] = $page->getUid();
        }

        return array_reverse($breadcrumb_uids);
    }

    /**
     * Tells whether getUrl() should return the redirect url or BB5 url.
     *
     * @param  boolean              $useUrlRedirect
     *
     * @return Page
     */
    public function setUseUrlRedirect($useUrlRedirect)
    {
        $this->useUrlRedirect = $useUrlRedirect;

        return $this;
    }

    /**
     * Should getUrl() return the redirect url or bb5 url?
     *
     * @return boolean
     */
    public function getUseUrlRedirect()
    {
        return $this->useUrlRedirect;
    }

    /**
     * Returns default template name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return str_replace(
            [
                'BackBee'.NAMESPACE_SEPARATOR.'NestedNode'.NAMESPACE_SEPARATOR,
                NAMESPACE_SEPARATOR
            ],
            [
                '',
                DIRECTORY_SEPARATOR
            ],
            get_class($this)
        );
    }

    /**
     * Return the uid of the layout.
     *
     * @return string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("layout_uid")
     */
    public function getLayoutUid()
    {
        return null !== $this->getLayout() ? $this->getLayout()->getUid() : '';
    }

    /**
     * Returns the label of the layout.
     *
     * @return string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("layout_label")
     */
    public function getLayoutLabel()
    {
        return null !== $this->getLayout() ? $this->getLayout()->getLabel() : '';
    }

    /**
     * Return the uid of site.
     *
     * @return string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_uid")
     */
    public function getSiteUid()
    {
        return null !== $this->getSite() ? $this->getSite()->getUid() : '';
    }

    /**
     * Returns the labe of site.
     *
     * @return string
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_label")
     */
    public function getSiteLabel()
    {
        return null !== $this->getSite() ? $this->getSite()->getLabel() : '';
    }

    /**
     * Returns available states.
     *
     * @return integer[]
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("states")
     */
    public function getStates()
    {
        $states = [];

        if (self::STATE_OFFLINE === $this->_state) {
            $states[] = self::STATE_OFFLINE;
        } elseif (self::STATE_HIDDEN === $this->_state) {
            $states[] = self::STATE_OFFLINE;
            $states[] = self::STATE_HIDDEN;
        } else {
            foreach (self::$STATES as $value) {
                if (0 !== ($this->_state & $value)) {
                    $states[] = $value;
                }
            }
        }

        return $states;
    }

    /**
     * Returns the uid of workflow State.
     *
     * @return string|null
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("workflow_uid")
     */
    public function getWorkflowStateUid()
    {
        return null !== $this->_workflow_state ? $this->_workflow_state->getUid() : null;
    }

    /**
     * Returns the label of workflow State.
     *
     * @return string|null
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("workflow_label")
     */
    public function getWorkflowStateLabel()
    {
        return null !== $this->_workflow_state ? $this->_workflow_state->getLabel() : null;
    }

    /**
     * Returns the code of the workflow state.
     *
     * @return integer
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("string")
     */
    public function getStateCode()
    {
        $code = $this->isOnline(true) ? '1' : '0';
        $code .= null !== $this->_workflow_state
            ? '_'.$this->_workflow_state->getCode()
            : ''
        ;

        return $code;
    }

    /**
     * Is the page hidden?
     *
     * @return boolean
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("boolean")
     */
    public function isHidden()
    {
        return 0 !== ($this->getState() & self::STATE_HIDDEN);
    }

    /**
     * Convert provided date to DateTime.
     *
     * @param  integer|\Datetime    $date               The date to convert to \DateTime.
     *
     * @return DateTime
     *
     * @throws InvalidArgumentException                 Raises if provided date is not an integer or an instance of \DateTime
     */
    private function convertTimestampToDateTime($date)
    {
        if (false === ($date instanceof \DateTime) && false === is_int($date)) {
            throw new InvalidArgumentException(
                'Page::convertTimestampToDateTime() expect date argument to be an integer or an instance of \DateTime'
            );
        } elseif (is_int($date)) {
            $date = new \DateTime(date('c', $date));
        }

        return $date;
    }

    /**
     * Returns the inherited content from parent, $default if not found.
     *
     * @param  integer              $index
     * @param  AbstractClassContent $default
     *
     * @return AbstractClassContent
     */
    private function getInheritedContent($index, AbstractClassContent $default)
    {
        if (
                null !== $this->getParent() &&
                $index < $this->getParent()->getContentSet()->count() &&
                null !== $this->getParent()->getContentSet()->item($index)
        ) {
            return $this->getParent()->getContentSet()->item($index);
        }

        return $default;
    }

    /**
     * Creates a new default content to be pushed in layout columns.
     *
     * @param  string               $classname
     * @param  boolean              $mainzone
     *
     * @return AbstractClassContent
     */
    private function createNewDefaultContent($classname, $mainzone = false)
    {
        $content = new $classname();
        if (null !== $content->getProperty('labelized-by')) {
            try {
                $label = $content;
                foreach (explode('->', (string) $label->getProperty('labelized-by')) as $property) {
                    if (is_object($label->$property)) {
                        $label = $label->$property;
                    } else {
                        break;
                    }
                }

                $label->$property = $this->getTitle();
            } catch (\Exception $e) {
            }
        }

        if (true === $mainzone) {
            $content->setMainNode($this);
        }

        return $content;
    }

    /**
     * Has the page children?
     *
     * @return boolean
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("boolean")
     */
    public function hasChildren($ignoreDeleted = true)
    {
        if ($ignoreDeleted) {
            return $this->hasMainSection() ? $this->getMainSection()->getHasChildren() : false;
        }

        return $this->hasMainSection() ? $this->getMainSection()->hasChildren() : false;
    }

    /**
     * Returns formated creation date
     *
     * @return string|null
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("created")
     */
    public function getCreatedTimestamp()
    {
        return $this->_created ? $this->_created->format('U') : null;
    }

    /**
     * Returns formated publishing date
     *
     * @return string|null
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("publishing")
     */
    public function getPublishingDate()
    {
        return $this->_publishing ? $this->_publishing->format('U') : null;
    }

    /**
     * Returns formated archiving date
     *
     * @return string|null
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("archiving")
     */
    public function getArchivingDate()
    {
        return $this->_archiving ? $this->_archiving->format('U') : null;
    }

    /**
     * Returns formated modified date
     *
     * @return string|null
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("modified")
     */
    public function getModifiedTimestamp()
    {
        return $this->_modified ? $this->_modified->format('U') : null;
    }

    /**
     * Returns te unique identifier.
     *
     * @return string
     * @codeCoverageIgnore
     *
     * @Serializer\Type("string")
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the level.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * Sets the level.
     *
     * @param  integer              $level
     *
     * @return Page
     * @throws InvalidArgumentException                 Raises if the value can not be cast to positive integer.
     */
    public function setLevel($level)
    {
        if (false === Numeric::isPositiveInteger($level, false)) {
            throw new InvalidArgumentException('A nested level must be a positive integer.');
        }
        $this->_level = $level;
        return $this;
    }

    /**
     * Returns the order position.
     *
     * @return integer
     * @codeCoverageIgnore
     */
    public function getPosition()
    {
        return $this->_position;
    }

    /**
     * Sets the position.
     * @param  integer              $position
     *
     * @return Page
     *
     * @throws InvalidArgumentException                 Raises if the value can not be cast to positive integer.
     */
    public function setPosition($position)
    {
        if (false === Numeric::isPositiveInteger($position, false)) {
            throw new InvalidArgumentException('A position must be a positive integer.');
        }
        $this->_position = $position;
        return $this;
    }

    /**
     * Returns the creation date.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Sets the date created.
     *
     * @param  \Datetime            $created
     *
     * @return Page
     */
    public function setCreated(\Datetime $created)
    {
        $this->_created = $created;
        return $this;
    }

    /**
     * Returns the last modified date.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Sets the date modified.
     *
     * @param  \Datetime            $modified
     *
     * @return Page
     */
    public function setModified(\Datetime $modified)
    {
        $this->_modified = $modified;
        return $this;
    }

    /**
     * Is this page has an associated section.
     *
     * @return boolean
     */
    public function hasMainSection()
    {
        return null !== $this->getMainSection();
    }

    /**
     * Returns the associated main section if exists, null otherwise.
     *
     * @return Section
     * @codeCoverageIgnore
     */
    public function getMainSection()
    {
        return $this->_mainsection;
    }

    /**
     * Sets the main section for this page.
     *
     * @param  Section              $section
     *
     * @return Page
     */
    public function setMainSection(Section $section)
    {
        if ($section === $this->_mainsection) {
            return $this;
        }

        $this->_mainsection = $section;
        $this->_position = 0;
        $this->_level = $section->getLevel();
        $section->setPage($this);

        return $this->setSection($section);
    }

    /**
     * Sets the section for this page.
     *
     * @param  Section              $section
     *
     * @return Page
     */
    public function setSection(Section $section)
    {
        if ($section !== $this->_mainsection) {
            $this->_mainsection = null;
            $this->_level = $section->getLevel() + 1;
            if (0 === $this->_position) {
                $this->_position = 1;
            }
        }

        $this->_section = $section;

        return $this;
    }

    /**
     * Returns the section of this page.
     *
     * @return Section
     */
    public function getSection()
    {
        if (null === $this->_section) {
            $this->setSection(new Section($this->getUid(), array('page' => $this)));
        }

        return $this->_section;
    }

    /**
     * Is the page is a leaf?
     *
     * @return boolean                                  True if the node if a leaf of tree, false otherwise.
     */
    public function isLeaf()
    {
        return $this->hasMainSection() ? $this->getSection()->isLeaf() : true;
    }

    /**
     * Is this page is an ancestor of the provided one?
     *
     * @param  Page                 $page
     * @param  boolean              $strict             Optional, if true (default) this page is excluded of ancestors list.
     *
     * @return boolean                                  True if this page is an anscestor of provided page, false otherwise.
     */
    public function isAncestorOf(Page $page, $strict = true)
    {
        if (!$this->hasMainSection()) {
            return ($this === $page && false === $strict);
        }
        return $this->getSection()->isAncestorOf($page->getSection(), $strict) || $page->getParent() === $this;
    }

    /**
     * Is this page is a descendant of the provided one?
     *
     * @param  Page                 $page
     * @param  boolean              $strict             Optional, if truz (default) this page is excluded of descendants list.
     *
     * @return boolean                                  True if this page is a descendant of provided page, false otherwise.
     */
    public function isDescendantOf(Page $page, $strict = true)
    {
        if ($this === $page) {
            return !$strict;
        }

        if (!$this->hasMainSection()) {
            return $page === $this->getParent() || $this->getSection()->isDescendantOf($page->getSection());
        }

        return $this->getSection()->isDescendantOf($page->getSection(), $strict);
    }

    /**
     * Returns the root page.
     *
     * @return Page
     */
    public function getRoot()
    {
        if ($this->getSection()->getRoot() instanceof Section) {
            return $this->getSection()->getRoot()->getPage();
        }

        return null;
    }

    /**
     * Is the page a root?
     *
     * @return boolean                                  True if the page is root of tree, false otherwise.
     */
    public function isRoot()
    {
        return $this->hasMainSection() && null === $this->getSection()->getParent();
    }

    /**
     * Sets the root node.
     *
     * @param  Page                 $root
     *
     * @return Page
     */
    public function setRoot(Page $root)
    {
        if ($this->hasMainSection()) {
            $this->getMainSection()->setRoot($root->getRoot()->getMainSection());
        }

        return $this;
    }

    /**
     * Sets the parent node.
     *
     * @param  Page                 $parent
     *
     * @return Page
     */
    public function setParent(Page $parent)
    {
        if (!$parent->hasMainSection()) {
            throw new InvalidArgumentException('A parent page must be a section');
        }

        if (!$this->hasMainSection() || $this->isRoot()) {
            $this->setSection($parent->getSection());
        } else {
            $this->getSection()->setParent($parent->getSection());
        }
        return $this;
    }

    /**
     * Returns the parent page, null if this page is root.
     *
     * @return Page|null
     */
    public function getParent()
    {
        $section = $this->getSection();
        if ($this->hasMainSection()) {
            if ($section->isRoot()) {
                return null;
            } elseif ($section->getParent() instanceof Section) {
                return $section->getParent()->getPage();
            } else {
                return null;
            }
        }

        return $section->getPage();
    }

    /**
     * Returns the parent uid, null if this page is root.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Type("string")
     * @Serializer\SerializedName("parent_uid")
     */
    public function getParentUid()
    {
        return null !== $this->getParent() ? $this->getParent()->getUid() : '';
    }
    /**
     * Returns the nested node left position.
     *
     * @return integer
     */
    public function getLeftnode()
    {
        return $this->getSection()->getLeftnode();
    }

    /**
     * Returns the nested node right position.
     *
     * @return integer
     */
    public function getRightnode()
    {
        return $this->getSection()->getRightnode();
    }
}
