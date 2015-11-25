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

namespace BackBee\CoreDomain\ClassContent;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Util\ClassUtils;
use BackBee\ClassContent\Exception\ClassContentException;
use BackBee\CoreDomainBundle\AutoLoader\Exception\ClassNotFoundException;
use BackBee\CoreDomain\NestedNode\Page;

//@todo gvf
if (false === defined('NAMESPACE_SEPARATOR')) {
    define('NAMESPACE_SEPARATOR', '\\');
}
use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract class for content object in BackBee.
 *
 * Basicaly every BackBee content extends AbstractClassContent
 * A content is also an persistant Doctrine entity
 *
 * A content has several states :
 *
 * * STATE_NEW : new content, revision number to 0
 * * STATE_NORMAL : last commited content
 * * STATE_LOCKED : content locked on writing
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\CoreDomainBundle\ClassContent\Repository\ClassContentRepository")
 * @ORM\Table(
 *   name="bb_content",
 *   indexes={
 *     @ORM\Index(name="IDX_MODIFIED", columns={"modified"}),
 *     @ORM\Index(name="IDX_STATE", columns={"state"}),
 *     @ORM\Index(name="IDX_NODEUID", columns={"node_uid"}),
 *     @ORM\Index(name="IDX_CLASSNAME", columns={"classname"})
 *   }
 * )
 * @ORM\HasLifecycleCallbacks
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="classname", type="string")
 * @ORM\DiscriminatorMap({"ContentSet" = "BackBee\CoreDomain\ClassContent\ContentSet"})
 */
abstract class AbstractClassContent extends AbstractContent
{
    /**
     * New content, revision number to 0.
     *
     * @var int
     */
    const STATE_NEW = 1000;

    /**
     * Last commited content.
     *
     * @var int
     */
    const STATE_NORMAL = 1001;

    /**
     * Content locked on writing.
     *
     * @var int
     */
    const STATE_LOCKED = 1002;

    /**
     * The many to many association between this content and its subcontent.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="BackBee\CoreDomain\ClassContent\AbstractClassContent", inversedBy="_parentcontent", cascade={"persist", "detach", "merge", "refresh"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="bb_content_has_subcontent",
     *     joinColumns={@ORM\JoinColumn(name="parent_uid", referencedColumnName="uid")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="content_uid", referencedColumnName="uid")}
     * )
     */
    protected $_subcontent;

    /**
     * The main nested node (page).
     *
     * @var \BackBee\CoreDomain\NestedNode\Page
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\NestedNode\Page", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="node_uid", referencedColumnName="uid")
     */
    protected $_mainnode;

    /**
     * The many to many association between this content and its parent content.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="BackBee\CoreDomain\ClassContent\AbstractClassContent", mappedBy="_subcontent", fetch="EXTRA_LAZY")
     */
    protected $_parentcontent;

    /**
     * The revisions of the content.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\ClassContent\Revision", mappedBy="_content", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"_revision" = "DESC"})
     */
    protected $_revisions;

    /**
     * The indexed values of elements.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\ClassContent\Indexation", mappedBy="_content", cascade={"all"}, fetch="EXTRA_LAZY")
     */
    protected $_indexation;

    /**
     * Store the map associating content uid to subcontent index.
     *
     * @var array
     */
    protected $subcontentmap = [];

    /**
     * The optionnal personnal draft of this content.
     *
     * @var \BackBee\CoreDomain\ClassContent\Revision
     */
    protected $draft;

    /**
     * Is this content persisted.
     *
     * @var boolean
     */
    protected $isloaded;

    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the content
     * @param array  $options Initial options for the content:
     *                        - accept      array Acceptable class names for the value
     *                        - maxentry    int The maxentry in value
     *                        - default     array default value for datas
     */
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);

        $this->_indexation = new ArrayCollection();
        $this->_subcontent = new ArrayCollection();
        $this->_parentcontent = new ArrayCollection();
        $this->_revisions = new ArrayCollection();
        $this->isloaded = false;
        $this->_state = self::STATE_NEW;

        $this->setOptions($options);
    }

    /**
     * Returns the associated page of the content if exists.
     *
     * @return \BackBee\CoreDomain\NestedNode\Page|NULL
     * @codeCoverageIgnore
     */
    public function getMainNode()
    {
        return $this->_mainnode;
    }

    /**
     * Set the main page to this content.
     *
     * @param \BackBee\CoreDomain\NestedNode\Page $node
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent the current instance
     * @codeCoverageIgnore
     */
    public function setMainNode(Page $node = null)
    {
        $this->_mainnode = $node;

        return $this;
    }

    /**
     * Returns the parent collection of the content if exists.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|NULL
     * @codeCoverageIgnore
     */
    public function getParentContent()
    {
        return $this->_parentcontent;
    }

    /**
     * Returns the collection of the subcontent if exists.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|NULL
     * @codeCoverageIgnore
     */
    public function getSubcontent()
    {
        return $this->_subcontent;
    }

    /**
     * Returns the collection of revisions of the content.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @codeCoverageIgnore
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Add a new revision to the collection.
     *
     * @param \BackBee\CoreDomain\ClassContent\Revision $revision
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent the current instance
     * @codeCoverageIgnore
     */
    public function addRevision(Revision $revision)
    {
        $this->_revisions[] = $revision;

        return $this;
    }

    /**
     * Returns the collection of indexed values.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @codeCoverageIgnore
     */
    public function getIndexation()
    {
        return $this->_indexation;
    }

    /**
     * Returns the user draft of this content if exists.
     *
     * @return \BackBee\CoreDomain\ClassContent\Revision The current draft if exists, NULL otherwise
     * @codeCoverageIgnore
     */
    public function getDraft()
    {
        return $this->draft;
    }

    /**
     * Unsets current user draft.
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent the current instance
     * @codeCoverageIgnore
     */
    public function releaseDraft()
    {
        $this->draft = null;

        return $this;
    }

    /**
     * Associates an user's draft to this content.
     *
     * @param \BackBee\CoreDomain\ClassContent\Revision $draft
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent the current instance
     * @codeCoverageIgnore
     */
    public function setDraft(Revision $draft = null)
    {
        $this->draft = $draft;

        return $this;
    }

    /**
     * Prepares to commit an user's draft data for current content.
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent                         the current instance
     * @throws \BackBee\ClassContent\Exception\RevisionMissingException    Occurs if none draft is defined
     * @throws \BackBee\ClassContent\Exception\RevisionConflictedException Occurs if the revision is conlicted
     * @throws \BackBee\ClassContent\Exception\RevisionUptodateException   Occurs if the revision is already up to date
     */
    public function prepareCommitDraft()
    {
        if (null === $revision = $this->getDraft()) {
            throw new Exception\RevisionMissingException('Enable to commit: missing draft');
        }

        switch ($revision->getState()) {
            case Revision::STATE_ADDED:
            case Revision::STATE_MODIFIED:
                $revision->setRevision($revision->getRevision() + 1);
                $revision->setState(Revision::STATE_COMMITTED);

                $this->releaseDraft();

                $this->_label = $revision->getLabel();
                $this->_accept = $revision->getAccept();
                $this->_maxentry = $revision->getMaxEntry();
                $this->_minentry = $revision->getMinEntry();
                $this->_parameters = $revision->getAllParams();

                $this->setRevision($revision->getRevision())
                    ->setState(AbstractClassContent::STATE_NORMAL)
                    ->addRevision($revision)
                ;

                return $this;

            case Revision::STATE_CONFLICTED:
                throw new Exception\RevisionConflictedException('Content is in conflict, please resolve or revert it');
        }

        throw new Exception\RevisionUptodateException(sprintf('Content can not be commited (state : %s)', $revision->getState()));
    }

    /**
     * Returns TRUE if the content is an entity managed by doctrine.
     *
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isLoaded()
    {
        return $this->isloaded;
    }

    /**
     * Initialized datas on postLoad doctrine event.
     *
     * @codeCoverageIgnore
     */
    public function postLoad()
    {
        $tmpModified = $this->_modified;
        $this->isloaded = true;
        $this->initData();
        $this->_modified = $tmpModified;
    }

    /**
     * Alternative recursive clone method, created because of problems related to doctrine clone method.
     *
     * @param \BackBee\CoreDomain\NestedNode\Page $originPage
     *
     * @return \BackBee\CoreDomain\ClassContent\ContentSet
     */
    public function createClone(Page $originPage = null)
    {
        $class = ClassUtils::getRealClass($this);
        $clone = new $class(null, null);
        $clone->_accept = $this->_accept;
        $clone->_maxentry = $this->_maxentry;
        $clone->_minentry = $this->_minentry;
        $clone->_parameters = $this->_parameters;
        $clone->_mainnode = $this->_mainnode;

        if (
            null !== $originPage
            && is_array($originPage->cloningData)
            && array_key_exists('contents', $originPage->cloningData)
        ) {
            $originPage->cloningData['contents'][$this->getUid()] = $clone;
        }

        if (!($this instanceof ContentSet)) {
            foreach ($this->_data as $key => $values) {
                foreach ($values as $type => &$value) {
                    if (is_array($value)) {
                        $keys = array_keys($value);
                        $values = array_values($value);

                        $type = end($keys);
                        $value = end($values);
                    }

                    if (!in_array($type, ['scalar', 'array'])) {
                        foreach ($this->_subcontent as $subcontent) {
                            if ($subcontent->getUid() == $value) {
                                $newsubcontent = $subcontent->createClone($originPage);
                                $clone->$key = $newsubcontent;
                                break;
                            }
                        }
                    } else {
                        $clone->$key = $value;
                    }
                }
                unset($value);
            }
        }

        return $clone;
    }

    /**
     * Returns the content revision.
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent
     * @codeCoverageIgnore
     */
    protected function getContentInstance()
    {
        return $this;
    }

    /**
     * Sets options at the construction of a new instance.
     *
     * @param array $options Initial options for the content:
     *                       - label       the label of the content
     *                       - parameters  a set of parameters for the content
     *                       - default     array default value for datas
     *
     * @return AbstractClassContent
     */
    protected function setOptions($options = null)
    {
        if (null !== $options) {
            $options = (array) $options;

            if (isset($options['label'])) {
                $this->_label = $options['label'];
            }

            if (isset($options['default'])) {
                $options['default'] = (array) $options['default'];
                foreach ($options['default'] as $var => $value) {
                    $this->_data[$var] = array();
                    $this->_maxentry[$var] = 1;
                    $this->_minentry[$var] = 0;
                    $this->__set($var, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Sets a collection of datas
     * Has to be overwritten by generalizations.
     *
     * @codeCoverageIgnore
     */
    protected function initData()
    {
        // Has to be overwritten by generalizations
        if ($this instanceof ContentSet) {
            $this->setProperty('is_container', true);
        } else {
            $this->setProperty('is_container', false);
        }
    }

    /**
     * Dynamically adds and sets new element to this content.
     *
     * @param string  $var          The name of the element
     * @param string  $type         Optional, the type of the element, scalar by default
     * @param array   $options      Optional, initial options for the content (see this constructor)
     * @param Boolean $updateAccept Optional, dynamically accept (default) or not the type for the new element.
     *
     * @return AbstractClassContent The current instance.
     */
    protected function defineData($var, $type = 'scalar', $options = null, $updateAccept = true)
    {
        if (true === $updateAccept) {
            $this->_addAcceptedType($type, $var);
        }

        if (!empty($options)) {
            $this->defaultOptions[$var] = $options;
        }

        if (!array_key_exists($var, $this->_data)) {
            $this->_data[$var] = [];
            $this->_maxentry[$var] = (!is_null($options) && isset($options['maxentry'])) ? $options['maxentry'] : 1;
            $this->_minentry[$var] = (!is_null($options) && isset($options['minentry'])) ? $options['minentry'] : 0;

            $values = [];
            if (is_array($options) && array_key_exists('default', $options)) {
                $values = (array) $options['default'];

                if ('scalar' !== $type) {
                    foreach ($values as &$value) {
                        $value = new $type(null, $options);
                    }
                    unset($value);
                }
            } else {
                try {
                    $values[] = ('scalar' === $type) ? '' : new $type(null, $options);
                } catch (ClassNotFoundException $ex) {
                    self::onUnknownClassname(sprintf('Unknown classname `%s`.', $type), $ex);
                }
            }

            return $this->__set($var, $values);
        }

        return $this;
    }

    /**
     * Dynamically add and set new parameter to this content.
     *
     * @param string $var     the name of the element
     * @param string $type    the type
     * @param array  $default default option for the parameter
     *
     * @return AbstractClassContent
     */
    protected function defineParam($var, $default = null)
    {
        $values = ['value' => null];
        if (!is_array($default) || !array_key_exists('value', $default)) {
            $values['value'] = $default;
        } else {
            $values = $default;
        }

        if (isset($this->defaultParams[$var])) {
            $values = array_merge($values, $this->defaultParams[$var]);
        }

        $this->defaultParams[$var] = $values;

        return $this;
    }

    /**
     * Adds a new accepted type to the element.
     *
     * @param string $type the type to accept
     * @param string $var  the element
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent The current instance
     */
    protected function _addAcceptedType($type, $var = null)
    {
        if (null === $var) {
            return $this;
        }

        if (!array_key_exists($var, $this->_accept)) {
            $this->_accept[$var] = array();
        }

        $types = (array) $type;
        foreach ($types as $type) {
            if ('scalar' !== $type && 'array' !== $type) {
                $type = self::getShortClassname($type);
            }

            if (!in_array($type, $this->_accept[$var])) {
                $this->_accept[$var][] = $type;
            }
        }

        return $this;
    }

    /**
     * Adds a subcontent to the collection.
     *
     * @param \BackBee\CoreDomain\ClassContent\AbstractClassContent $value
     *
     * @return string the unique identifier of the add subcontent
     */
    protected function _addSubcontent(AbstractClassContent $value)
    {
        if (!$this->_subcontent->indexOf($value)) {
            $this->_subcontent->add($value);
        }

        $this->subcontentmap[$value->getUid()] = $this->_subcontent->indexOf($value);

        return $value->getUid();
    }

    /**
     * Removes the association with subcontents of the element $var.
     *
     * @param string $var
     */
    protected function _removeSubcontent($var)
    {
        if ($this->acceptSubcontent($var)) {
            foreach ($this->_data[$var] as $type => $value) {
                if (is_array($value)) {
                    $keys = array_keys($value);
                    $values = array_values($value);

                    $type = end($keys);
                    $value = end($values);
                }

                foreach ($this->_subcontent as $subcontent) {
                    if ($subcontent->getUid() === $value) {
                        $this->_subcontent->removeElement($subcontent);
                        break;
                    }
                }
            }

            $this->updateSubcontentMap();
        }
    }

    /**
     * Updates the associationing map between subcontent and uid.
     */
    private function updateSubcontentMap()
    {
        $this->subcontentmap = array();

        $index = 0;
        foreach ($this->_subcontent as $subcontent) {
            $this->subcontentmap[$subcontent->getUid()] = $index++;
        }
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Public methods overload by Revision                  */
    /*                          if a draft is defined                         */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Magical function to set value to given element.
     *
     * @param string $var   The name of the element
     * @param mixed  $value The value to set
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent The current instance content
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     * @codeCoverageIgnore
     */
    public function __set($var, $value)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->__set($var, $value) : parent::__set($var, $value);
    }

    /**
     * Magical function to check the setting of an element.
     *
     * @param string $var the name of the element
     *
     * @return boolean Returns TRUE if $var is element
     * @codeCoverageIgnore
     */
    public function __isset($var)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->__isset($var) : parent::__isset($var);
    }

    /**
     * Magical function to unset an element.
     *
     * @param string $var The name of the element to unset
     * @return boolean Returns TRUE if $var is unsetted
     * @codeCoverageIgnore
     */
    public function __unset($var)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->__unset($var) : parent::__unset($var);
    }

    /**
     * Return the label of the content.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getLabel() : parent::getLabel();
    }

    /**
     * Returns the current accepted subcontents.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getAccept()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getAccept() : parent::getAccept();
    }

    /**
     * Returns the raw datas array of the content.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getDataToObject()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getDataToObject() : parent::getDataToObject();
    }

    /**
     * Get the maxentry of the content.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getMaxEntry()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getMaxEntry() : parent::getMaxEntry();
    }

    /**
     * Gets the minentry of the content.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getMinEntry()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getMinEntry() : parent::getMinEntry();
    }

    /**
     * Returns the creation date of the content.
     *
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return null !== $this->getDraft() ? $this->getDraft()->getCreated() : parent::getCreated();
    }

    /**
     * Returns the last modified date of the content.
     *
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getModified() : parent::getModified();
    }

    /**
     * Returns the revision number of the content.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getRevision()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getRevision() : parent::getRevision();
    }

    /**
     * Checks if the element accept subcontent.
     *
     * @param string $var the element
     *
     * @return Boolean TRUE if a subcontents are accepted, FALSE otherwise
     * @codeCoverageIgnore
     */
    public function acceptSubcontent($var)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->acceptSubcontent($var) : parent::acceptSubcontent($var);
    }

    /**
     * Returns the mode to be used by current content.
     *
     * @return string
     */
    public function getMode()
    {
        $rendermode = $this->getParamValue('rendermode');

        return (is_array($rendermode)) ? reset($rendermode) : null;
    }

    /**
     * Alias of ::getDefaultImageName but you can override this to return custom image.
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->getDefaultImageName();
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of RenderableInterface                        */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Return the data of this content.
     *
     * @param  string  $var        The element to be return, if NULL, all datas are returned
     * @param  Boolean $forceArray Force the return as array
     *
     * @return mixed                                                    Could be either one or array of scalar, array, AbstractClassContent instance
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     * @throws \BackBee\AutoLoader\Exception\ClassNotFoundException     Occurs if the class of a subcontent can not be loaded
     * @codeCoverageIgnore
     */
    public function getData($var = null, $forceArray = false)
    {
        return null !== $this->getDraft()
            ? $this->getDraft()->getData($var, $forceArray)
            : parent::getData($var, $forceArray)
        ;
    }

    /**
     * Parameters setter.
     *
     * @param string $key   the parameter name to set, if NULL all the parameters array wil be set
     * @param mixed  $value the parameter value or all the parameters if $key is NULL
     *
     * @return AbstractClassContent
     */
    public function setParam($key, $value = null)
    {

        return null !== $this->getDraft() ? $this->getDraft()->setParam($key, $value) : parent::setParam($key, $value);
    }

    /**
     * Returns defined parameters.
     *
     * @param string $key The parameter to be return, if NULL, all parameters are returned
     *
     * @return mixed the parameter value or NULL if unfound
     */
    public function getParam($key)
    {
        return null !== $this->getDraft() ? $this->getDraft()->getParam($key) : parent::getParam($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllParams()
    {
        return null !== $this->getDraft() ? $this->getDraft()->getAllParams() : parent::getAllParams();
    }

    /**
     * Checks for state of the content before rendering it.
     *
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return AbstractClassContent::STATE_NORMAL === $this->getState();
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                         Deprecated methods ?                           */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Unsets a subcontent to the current collection.
     *
     * @param \BackBee\CoreDomain\ClassContent\AbstractClassContent $subContent
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent
     */
    public function unsetSubContent(AbstractClassContent $subContent)
    {
        foreach ($this->_data as $key => $value) {
            if (is_array($value)) {
                $totalContent = count($value);
                foreach ($value as $cKey => $cValue) {
                    $contentUid = $cValue;
                    if (is_array($cValue)) {
                        $contentUid = array_values($cValue);
                        $contentUid = end($contentUid);
                    }

                    if ($subContent->getUid() == $contentUid) {
                        if (1 === $totalContent) {
                            $this->_data[$key] = [];
                            $this->_subcontent->removeElement($subContent);
                        } else {
                            unset($value[$cKey]);
                            $this->_data[$key] = $value;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Returns a subcontent instance by its type and value, FALSE if not found.
     *
     * @param string $type  The classname of the subcontent
     * @param string $value The value of the subcontent (uid)
     *
     * @return \BackBee\CoreDomain\ClassContent\AbstractClassContent|FALSE
     */
    protected function getContentByDataValue($type, $value)
    {
        if (class_exists($type)) {
            $index = 0;
            foreach ($this->_subcontent as $subcontent) {
                $this->subcontentmap[$subcontent->getUid()] = $index++;
                if ($subcontent->getUid() === $value) {
                    return $subcontent;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * Check if a variable is part of accepted types
     *
     * @param mixed $var the variable to be checked
     *
     * @return boolean|ClassContentException
     */
    public function getAcceptedType($var)
    {
        $accepts = ($this->getAccept());
        if (isset($accepts[$var]) && !empty($accepts[$var])) {
            return reset($accepts[$var]);
        } else {
            throw new ClassContentException(
                sprintf('Unknown element %s in %s.', $var, get_class($this)),
                ClassContentException::UNKNOWN_PROPERTY
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize($format = self::JSON_DEFAULT_FORMAT)
    {
        $data = parent::jsonSerialize($format);

        if (self::JSON_INFO_FORMAT === $format) {
            return $data;
        }

        if (!isset($data['label'])) {
            $data['label'] = $this->getProperty('name');
        }

        if (self::JSON_CONCISE_FORMAT === $format) {
//            @todo somehow get path or resolve it to allow custom locations?
            $data['image'] = '/media/' . $this->getImageName();

            return $data;
        }

        if (self::JSON_DEFINITION_FORMAT === $format) {
            $data['parameters'] = 0 === count($this->getDefaultParams())
                ? new \ArrayObject()
                : $this->getDefaultParams()
            ;
        }

        $properties = $this->getProperty();
        unset(
            $properties['indexation'],
            $properties['labelized-by'],
            $properties['clonemode'],
            $properties['cache_lifetime']
        );

        $data = array_merge([
            'defaultOptions' => 0 === count($this->defaultOptions) ? new \ArrayObject() : $this->defaultOptions,
            'properties'     => $properties,
            'image'          => self::JSON_DEFINITION_FORMAT === $format
                ? $this->getDefaultImageName()
                : $this->getImageName(),
        ], $data);

        return $data;
    }
}
