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

namespace BackBee\CoreDomain\Site;

use BackBee\CoreDomain\Security\Acl\Domain\AbstractObjectIdentifiable;
use BackBee\Utils\Numeric;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use JMS\Serializer\Annotation as Serializer;

use Doctrine\ORM\Mapping as ORM;

/**
 * A website layout.
 *
 * If the layout is not associated to a website, it is proposed as layout template
 * to webmasters
 *
 * The stored data is a serialized standard object. The object must have the
 * following structure :
 *
 * layout: {
 *   templateLayouts: [      // Array of final droppable zones
 *     zone1: {
 *       id:                 // unique identifier of the zone
 *       defaultContainer:   // default AbstractClassContent drop at creation
 *       target:             // array of accepted AbstractClassContent dropable
 *       gridClassPrefix:    // prefix of responsive CSS classes
 *       gridSize:           // size of this zone for responsive CSS
 *     },
 *     ...
 *   ]
 * }
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\CoreDomain\Site\Repository\LayoutRepository")
 * @ORM\Table(name="bb_layout",indexes={@ORM\Index(name="IDX_SITE", columns={"site_uid"})})
 * @ORM\HasLifecycleCallbacks
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Layout extends AbstractObjectIdentifiable
{
    /**
     * The unique identifier.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32, name="uid")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $uid;

    /**
     * The label of this layout.
     *
     * @var string
     * @ORM\Column(type="string", name="label", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $label;

    /**
     * The file name of the layout.
     *
     * @var string
     * @ORM\Column(type="string", name="path", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $path;

    /**
     * The seralized data.
     *
     * @var string
     * @ORM\Column(type="text", name="data", nullable=false)
     */
    protected $data;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $modified;

    /**
     * The optional path to the layout icon.
     *
     * @var string
     * @ORM\Column(type="string", name="picpath", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $picpath;

    /**
     * Optional owner site.
     *
     * @var \BackBee\CoreDomain\Site\Site
     * @ORM\ManyToOne(targetEntity="BackBee\CoreDomain\Site\Site", inversedBy="_layouts", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $site;

    /**
     * Store pages using this layout.
     * var \Doctrine\Common\Collections\ArrayCollection.
     *
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\NestedNode\Page", mappedBy="_layout", fetch="EXTRA_LAZY")
     */
    protected $pages;

    /**
     * Layout states.
     *
     * var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackBee\CoreDomain\Workflow\State", fetch="EXTRA_LAZY", mappedBy="_layout")
     */
    protected $states;

    /**
     * The content's parameters.
     *
     * @var array
     * @ORM\Column(type="array", name="parameters", nullable = true)
     *
     * @Serializer\Expose
     * @Serializer\Type("array")
     */
    protected $parameters = array();

    /**
     * The DOM document corresponding to the data.
     *
     * @var \DOMDocument
     */
    protected $domdocument;

    /**
     * Is the layout datas are valid ?
     *
     * @var Boolean
     */
    protected $isValid;

    /**
     * The final DOM zones on layout.
     *
     * @var array
     */
    protected $zones;

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the layout
     * @param array  $options Initial options for the layout:
     *                        - label      the default label
     *                        - path       the path to the template file
     */
    public function __construct($uid = null, $options = null)
    {
        $this->uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->created = new \DateTime();
        $this->modified = new \DateTime();

        $this->pages = new ArrayCollection();

        if (true === is_array($options)) {
            if (true === array_key_exists('label', $options)) {
                $this->setLabel($options['label']);
            }
            if (true === array_key_exists('path', $options)) {
                $this->setPath($options['path']);
            }
        }

        $this->states = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Returns the unique identifier.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Returns the label.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Returns the file name of the layout.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the serialized data of the layout.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns the unserialzed object for the layout.
     *
     * @codeCoverageIgnore
     *
     * @return \StdClass
     */
    public function getDataObject()
    {
        return json_decode($this->getData());
    }

    /**
     * Returns the path to the layout icon if defined, NULL otherwise.
     *
     * @codeCoverageIgnore
     *
     * @return string|NULL
     */
    public function getPicPath()
    {
        return $this->picpath;
    }

    /**
     * Returns the owner site if defined, NULL otherwise.
     *
     * @codeCoverageIgnore
     *
     * @return \BackBee\CoreDomain\Site\Site|NULL
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Return the final zones (ie with contentset) for the layout.
     *
     * @return array|NULL Returns an array of zones or NULL is the layout datas
     *                    are invalid.
     */
    public function getZones()
    {
        if (null === $this->zones) {
            if (true === $this->isValid()) {
                $this->zones = array();
                $zonesWithChild = array();

                $zones = $this->getDataObject()->templateLayouts;
                foreach ($zones as $zone) {
                    $zonesWithChild[] = substr($zone->target, 1);
                }

                foreach ($zones as $zone) {
                    if (false === in_array($zone->id, $zonesWithChild)) {
                        if (false === property_exists($zone, 'mainZone')) {
                            $zone->mainZone = false;
                        }

                        if (false === property_exists($zone, 'defaultClassContent')) {
                            $zone->defaultClassContent = null;
                        }

                        $zone->options = $this->getZoneOptions($zone);

                        array_push($this->zones, $zone);
                    }
                }
            }
        }

        return $this->zones;
    }

    /**
     * Returns defined parameters.
     *
     * @param string $var The parameter to be return, if NULL, all parameters are returned
     *
     * @return mixed the parameter value or NULL if unfound
     */
    public function getParam($var = null)
    {
        $param = $this->parameters;
        if (null !== $var) {
            if (isset($this->parameters[$var])) {
                $param = $this->parameters[$var];
            } else {
                $param = null;
            }
        }

        return $param;
    }

    /**
     * Goes all over the $param and keep looping until $pieces is empty to return
     * the values user is looking for.
     *
     * @param mixed $param
     * @param array $pieces
     *
     * @return mixed
     */
    private function getRecursivelyParam($param, array $pieces)
    {
        if (0 === count($pieces)) {
            return $param;
        }

        $key = array_shift($pieces);
        if (false === isset($param[$key])) {
            return;
        }

        return $this->getRecursivelyParam($param[$key], $pieces);
    }

    /**
     * Returns the zone at the index $index.
     *
     * @param int $index
     *
     * @return \StdClass|null
     *
     * @throws InvalidArgumentException
     */
    public function getZone($index)
    {
        if (false === Numeric::isPositiveInteger($index, false)) {
            throw new InvalidArgumentException('Invalid integer value.');
        }

        if (null !== $zones = $this->getZones()) {
            if ($index < count($zones)) {
                return $zones[$index];
            }
        }

        return;
    }

    /**
     * Generates and returns a DOM document according to the unserialized data object.
     *
     * @return \DOMDocument|NULL Returns a DOM document or NULL is the layout datas
     *                           are invalid.
     */
    public function getDomDocument()
    {
        if (null === $this->domdocument) {
            if (true === $this->isValid()) {
                $mainLayoutRow = new \DOMDocument('1.0', 'UTF-8');
                $mainNode = $mainLayoutRow->createElement('div');
                $mainNode->setAttribute('class', 'row');

                $clearNode = $mainLayoutRow->createElement('div');
                $clearNode->setAttribute('class', 'clear');

                $mainId = '';
                $zones = array();
                foreach ($this->getDataObject()->templateLayouts as $zone) {
                    $mainId = $zone->defaultContainer;
                    $class = $zone->gridClassPrefix.$zone->gridSize;

                    if (true === property_exists($zone, 'alphaClass')) {
                        $class .= ' '.$zone->alphaClass;
                    }

                    if (true === property_exists($zone, 'omegaClass')) {
                        $class .= ' '.$zone->omegaClass;
                    }

                    if (true === property_exists($zone, 'typeClass')) {
                        $class .= ' '.$zone->typeClass;
                    }

                    $zoneNode = $mainLayoutRow->createElement('div');
                    $zoneNode->setAttribute('class', trim($class));
                    $zones['#'.$zone->id] = $zoneNode;

                    $parentNode = isset($zones[$zone->target]) ? $zones[$zone->target] : $mainNode;
                    $parentNode->appendChild($zoneNode);
                    if (true === property_exists($zone, 'clearAfter')
                            && 1 == $zone->clearAfter) {
                        $parentNode->appendChild(clone $clearNode);
                    }
                }

                $mainNode->setAttribute('id', substr($mainId, 1));
                $mainLayoutRow->appendChild($mainNode);

                $this->domdocument = $mainLayoutRow;
            }
        }

        return $this->domdocument;
    }

    /**
     * Checks for a valid structure of the unserialized data object.
     *
     * @return Boolean Returns TRUE if the data object is valid, FALSE otherwise
     */
    public function isValid()
    {
        if (null === $this->isValid) {
            $this->isValid = false;

            if (null !== $data_object = $this->getDataObject()) {
                if (true === property_exists($data_object, 'templateLayouts')
                        && true === is_array($data_object->templateLayouts)
                        && 0 < count($data_object->templateLayouts)) {
                    $this->isValid = true;

                    foreach ($data_object->templateLayouts as $zone) {
                        if (false === property_exists($zone, 'id')
                                || false === property_exists($zone, 'defaultContainer')
                                || false === property_exists($zone, 'target')
                                || false === property_exists($zone, 'gridClassPrefix')
                                || false === property_exists($zone, 'gridSize')) {
                            $this->isValid = false;
                            break;
                        }
                    }
                }
            }
        }

        return $this->isValid;
    }

    /**
     * Sets the label.
     *
     * @codeCoverageIgnore
     *
     * @param string $label
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the filename of the layout.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Sets the data associated to the layout.
     * No validation checks are performed at this step.
     *
     * @param mixed $data
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setData($data)
    {
        if (true === is_object($data)) {
            return $this->setDataObject($data);
        }

//        @tdo why set all to null when setting data?
        $this->picpath = null;
        $this->isValid = null;
        $this->domdocument = null;
        $this->zones = null;

        $this->data = $data;

        return $this;
    }

    /**
     * Sets the data associated to the layout.
     * None validity checks are performed at this step.
     *
     * @param mixed $data
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setDataObject($data)
    {
        if (true === is_object($data)) {
            $data = json_encode($data);
        }

        return $this->setData($data);
    }

    /**
     * Sets the path to the layout icon.
     *
     * @codeCoverageIgnore
     *
     * @param string $picpath
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setPicPath($picpath)
    {
        $this->picpath = $picpath;

        return $this;
    }

    /**
     * Associates this layout to a website.
     *
     * @codeCoverageIgnore
     *
     * @param \BackBee\CoreDomain\Site\Site $site
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setSite(Site $site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Sets one or all parameters.
     *
     * @param string $var    the parameter name to set, if NULL all the parameters array wil be set
     * @param mixed  $values the parameter value or all the parameters if $var is NULL
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function setParam($var = null, $values = null)
    {
        if (null === $var) {
            $this->parameters = $values;
        } else {
            $this->parameters[$var] = $values;
        }

        return $this;
    }

    /**
     * Returns a contentset options according to the layout zone.
     *
     * @param \StdClass $zone
     *
     * @return array
     */
    private function getZoneOptions(\stdClass $zone)
    {
        $options = array(
            'parameters' => array(
                'class' => array(
                    'type' => 'scalar',
                    'options' => array('default' => 'row'),
                ),
            ),
        );

        if (true === property_exists($zone, 'accept')
                && true === is_array($zone->accept)
                && 0 < count($zone->accept)
                && $zone->accept[0] != '') {
            $options['accept'] = $zone->accept;

            $func = function (&$item, $key) {
                        $item = ('' == $item) ? null : 'BackBee\CoreDomain\ClassContent\\'.$item;
                    };

            array_walk($options['accept'], $func);
        }

        if (true === property_exists($zone, 'maxentry') && 0 < $zone->maxentry) {
            $options['maxentry'] = $zone->maxentry;
        }

        return $options;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("siteuid")
     */
    public function getSiteUid()
    {
        return null !== $this->site ? $this->site->getUid() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("sitelabel")
     */
    public function getSiteLabel()
    {
        return null !== $this->site ? $this->site->getLabel() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("data")
     */
    public function virtualGetData()
    {
        return json_decode($this->getData(), true);
    }

    /**
     * Add state.
     *
     * @param \BackBee\CoreDomain\Workflow\State $state
     *
     * @return \BackBee\CoreDomain\Site\Layout
     */
    public function addState(\BackBee\CoreDomain\Workflow\State $state)
    {
        $this->states[] = $state;

        return $this;
    }
    /**
     * Remove state.
     *
     * @param \BackBee\CoreDomain\Workflow\State $state
     */
    public function removeState(\BackBee\CoreDomain\Workflow\State $state)
    {
        $this->states->removeElement($state);
    }
    /**
     * Get states.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("workflowstates")
     */
    public function getWokflowStates()
    {
        $workflowStates = array(
            'online'  => array(),
            'offline' => array(),
        );

        foreach ($this->getStates() as $state) {
            if (0 < $code = $state->getCode()) {
                $workflowStates['online'][$code] = array(
                    'label' => $state->getLabel(),
                    'code'  => '1_'.$code,
                );
            } else {
                $workflowStates['offline'][$code] = array(
                    'label' => $state->getLabel(),
                    'code'  => '0_'.$code,
                );
            }
        }

        $workflowStates = array_merge(
            array('0' => array('label' => 'Hors ligne', 'code' => '0')),
            $workflowStates['offline'],
            array('1' => array('label' => 'En ligne', 'code' => '1')),
            $workflowStates['online']
        );

        return $workflowStates;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_final")
     */
    public function isFinal()
    {
        return (bool) $this->getParam('is_final');
    }
}
