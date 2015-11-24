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

namespace BackBee\CoreDomainBundle\Stream\ClassWrapper;

use BackBee\CoreDomainBundle\Stream\ClassWrapper\Exception\ClassWrapperException;
use BackBee\CoreDomainBundle\Stream\StreamWrapperInterface;

/**
 * Abstract class for content wrapper in BackBee 4
 * Implements IClassWrapper.
 *
 * BackBee defines bb.class protocol to include its class definition
 * Several wrappers could be defined for several storing formats:
 *  - yaml files
 *  - xml files
 *  - yaml stream stored in DB
 *  - ...
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @author      g.vilaseca <gonzalo.vilaseca@reiss.com>
 */
//@tod gvf these are no stream wrappers anymore
abstract class AbstractClassWrapper
{
    /**
     * The class to be extended by the class content loaded.
     *
     * @var string
     */
    protected $extends = '\BackBee\CoreDomain\ClassContent\AbstractClassContent';

    /**
     * The doctrine repository associated to the class content loaded.
     *
     * @var string
     */
    protected $repository = 'BackBee\CoreDomainBundle\ClassContent\Repository\ClassContentRepository';

    /**
     * Default php template to build the class file.
     *
     * @var string
     */
    protected $template =
        '<?php
namespace <namespace>;

/**
 * @\Doctrine\ORM\Mapping\Entity(repositoryClass="<repository>")
 * @\Doctrine\ORM\Mapping\Table(name="bb_content")
 * @\Doctrine\ORM\Mapping\HasLifecycleCallbacks
 * <docblock>
 */
class <classname> extends <extends> <interface>
{
    <trait>
    public function __construct($uid = NULL, $options = NULL)
    {
        parent::__construct($uid, $options);
        $this->initData();
    }

    protected function initData()
    {
        <defineDatas>
        <defineParam>
        <defineProps>
        parent::initData();
    }
}
';

    /**
     * Build the php code corresponding to the loading class.
     *
     * @return string The generated php code
     */
    protected function _buildClass($data)
    {
        $defineData = [];
        if (array_key_exists('elements', $data)) {
            $defineData = $this->_extractData($data['elements']);
        }

        $defineParam = [];
        if (array_key_exists('parameters', $data)) {
            $defineParam = $data['parameters'];
        }
        $defineProps = [];
        if (array_key_exists('properties', $data)) {
            $defineProps = $data['properties'];
        }

        $docBlock = '';
        foreach ($defineData as $key => $element) {
            $type = $element['type'];
            if ('scalar' === $type) {
                $type = 'string';
            }

            $docBlock .= "\n * @property " . $type . ' $' . $key . ' ' . (isset($element['options']['label']) ? $element['options']['label'] : '');
        }

        array_walk($defineData, function (&$value, $key) {
            $value = "->defineData('" . $key . "', '" . $value['type'] . "', " . var_export($value['options'], true) . ")";
        });
        array_walk($defineParam, function (&$value, $key) {
            $value = "->defineParam('" . $key . "', " . var_export($value, true) . ")";
        });
        array_walk($defineProps, function (&$value, $key) {
            $value = "->defineProperty('" . $key . "', " . var_export($value, true) . ")";
        });

        $phpCode = str_replace(array('<namespace>',
            '<classname>',
            '<repository>',
            '<extends>',
            '<interface>',
            '<trait>',
            '<defineDatas>',
            '<defineParam>',
            '<defineProps>',
            '<docblock>',), array($data['namespace'],
            $data['classname'],
            array_key_exists('repository', $data) ? $data['repository'] : $this->repository,
            array_key_exists('extends', $data) ? $data['extends'] : $this->extends,
            array_key_exists('interfaces', $data)? $data['interfaces']:'',
            array_key_exists('traits', $data)? $data['traits']:'',
            (0 < count($defineData)) ? '$this' . implode('', $defineData) . ';' : '',
            (0 < count($defineParam)) ? '$this' . implode('', $defineParam) . ';' : '',
            (0 < count($defineProps)) ? '$this' . implode('', $defineProps) . ';' : '',
            $docBlock,), $this->template);

        return $phpCode;
    }

    /**
     * Checks for a normalize var name.
     *
     * @param string $var The var name to check
     *
     * @throws ClassWrapperException Occurs for a syntax error
     */
    protected function _normalizeVar($var, $includeSeparator = false)
    {
        if ($includeSeparator) {
            $var = explode(NAMESPACE_SEPARATOR, $var);
        }

        $vars = (array)$var;

        $pattern = "/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";

        foreach ($vars as $var) {
            if ($var != '' && !preg_match($pattern, $var)) {
                throw new ClassWrapperException(sprintf('Syntax error: \'%s\'', $var));
            }
        }

        return implode(($includeSeparator) ? NAMESPACE_SEPARATOR : '', $vars);
    }

}
