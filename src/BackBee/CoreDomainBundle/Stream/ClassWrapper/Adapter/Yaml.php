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

namespace BackBee\CoreDomainBundle\Stream\ClassWrapper\Adapter;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as parserYaml;
use BackBee\CoreDomainBundle\Stream\ClassWrapper\AbstractClassWrapper;
use BackBee\CoreDomainBundle\Stream\ClassWrapper\Exception\ClassWrapperException;
use BackBee\Utils\File\File;

/**
 * Stream wrapper to interprete yaml file as class content description
 * Extends AbstractClassWrapper
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @author      g.vilaseca <gonzalo.vilaseca@reiss.com>
 */
class Yaml extends AbstractClassWrapper
{
    /**
     * @var
     */
    private $classConfigConfiguration;
    /**
     * @var
     */
    private $classContentNamespace;

    /**
     * Class constructor.
     */
    public function __construct($classConfigConfiguration, $classContentNamespace)
    {
        $this->classConfigConfiguration = $classConfigConfiguration;
        $this->classContentNamespace = $classContentNamespace;
    }

    /**
     * Extract and format data from parser.
     *
     * @param array $data
     *
     * @return array The extracted data
     */
    protected function _extractData($data)
    {
        $extractedData = array();

        foreach ($data as $key => $value) {
            $type = 'scalar';
            $options = array();

            if (is_array($value)) {
                if (isset($value['type'])) {
                    $type = $value['type'];
                    if (isset($value['default'])) {
                        $options['default'] = $value['default'];
                    }

                    if (isset($value['label'])) {
                        $options['label'] = $value['label'];
                    }

                    if (isset($value['maxentry'])) {
                        $options['maxentry'] = $value['maxentry'];
                    }

                    if (isset($value['parameters'])) {
                        $options['parameters'] = $value['parameters'];
                    }

                    if (isset($value['extra'])) {
                        $options['extra'] = $value['extra'];
                    }
                } else {
                    $type = 'array';
                    $options['default'] = $value;
                }
            } else {
                $value = trim($value);

                if (strpos($value, '!!') === 0) {
                    $typedValue = explode(' ', $value, 2);
                    $type = str_replace('!!', '', $typedValue[0]);
                    if (isset($typedValue[1])) {
                        $options['default'] = $typedValue[1];
                    }
                }
            }

            $extractedData[$key] = array('type' => $type, 'options' => $options);
        }

        return $extractedData;
    }

    /**
     * Checks the validity of the extracted data from yaml file.
     *
     * @param array $yamlData The yaml data
     *
     * @return Boolean Returns TRUE if data are valid, FALSE if not
     *
     * @throws ClassWrapperException Occurs when data are not valid
     */
    private function checkDatas($yamlData)
    {
        //  Converts yamlstructure to the structure needed for the build
        $buildData = [];
        try {
            if ($yamlData === false || !is_array($yamlData)  ) {
                throw new ClassWrapperException('Malformed class content description');
            }

                $contentDesc = $yamlData;
                foreach ($contentDesc as $key => $data) {
                    switch ($key) {
                        case 'extends':
                            $buildData['extends'] = $this->_normalizeVar($data, true);
                            if (substr($buildData['extends'], 0, 1) != NAMESPACE_SEPARATOR) {
                                $buildData['extends'] = NAMESPACE_SEPARATOR.$this->namespace.
                                    NAMESPACE_SEPARATOR.$buildData['extends'];
                            }

                            break;
                        case 'interfaces':
                            $data = false === is_array($data) ? array($data) : $data;
                            $buildData['interfaces'] = array();

                            foreach ($data as $i) {
                                $interface = $i;
                                if (NAMESPACE_SEPARATOR !== substr($i, 0, 1)) {
                                    $interface = NAMESPACE_SEPARATOR.$i;
                                }

                                // add interface only if it exists
                                if (true === interface_exists($interface)) {
                                    $buildData['interfaces'][] = $interface;
                                }
                            }

                            // build up interface use string
                            $str = implode(', ', $buildData['interfaces']);
                            if (0 < count($data['interfaces'])) {
                                $buildData['interfaces'] = 'implements '.$str;
                            } else {
                                $buildData['interfaces'] = '';
                            }

                            break;
                        case 'repository':
                            if (class_exists($data)) {
                                $buildData['repository'] = $data;
                            }
                            break;
                        case 'traits':
                            $data = false === is_array($data) ? array($data) : $data;
                            $buildData['traits'] = array();

                            foreach ($data as $t) {
                                $trait = $t;
                                if (NAMESPACE_SEPARATOR !== substr($t, 0, 1)) {
                                    $trait = NAMESPACE_SEPARATOR.$t;
                                }

                                // add traits only if it exists
                                if (true === trait_exists($trait)) {
                                    $buildData['traits'][] = $trait;
                                }
                            }

                            // build up trait use string
                            $str = implode(', ', $buildData['traits']);
                            if (0 < count($buildData['traits'])) {
                                $buildData['traits'] = 'use '.$str.';';
                            } else {
                                $buildData['traits'] = '';
                            }

                            break;
                        case 'elements':
                        case 'parameters':
                        case 'properties':
                            $values = array();
                            $data = (array) $data;
                            foreach ($data as $var => $value) {
                                $values[strtolower($this->_normalizeVar($var))] = $value;
                            }

                            $buildData[$key] = $values;
                            break;
                    }
                }
        } catch (ClassWrapperException $e) {
            throw new ClassWrapperException($e->getMessage(), 0, null, $this->_path);
        }

        return $buildData;
    }


    /**
     * Opens a stream content for a yaml file.
     *
     * @see BackBee\Stream\ClassWrapper.IClassWrapper::stream_open()
     *
     * @throws BBException           Occurs when none yamel files were found
     * @throws ClassWrapperException Occurs when yaml file is not a valid class content description
     */
    public function build($classpath)
    {
        list($classname, $namespace, $classConfigKey) = $this->normalizeClassname($classpath);


// @todo gvf
//            if (null !== $this->_cache) {
//                $expire = new \DateTime();
//                $expire->setTimestamp($this->_stat['mtime']);
//                $this->_data = $this->_cache->load(md5($this->_path), false, $expire);
//
//                if (false !== $this->_data) {
//                    return true;
//                }
//            }
            if (!array_key_exists($classConfigKey, $this->classConfigConfiguration)) {
                throw new \Exception(sprintf('Couldn\'t find class definition \'%s\'', $classpath));

            }
            if ($data = $this->checkDatas($this->classConfigConfiguration[$classConfigKey])) {
                $data['classname'] = $classname;
                $data['namespace'] = $namespace;
                return $this->_buildClass($data);

//                @todo gvf
//                if (null !== $this->_cache) {
//                    $this->_cache->save(md5($this->_path), $this->_data);
//                }

            }

//@todo gvf
//        seguir por aqui, cambiar la excepcion, no encuentra el fichero yml
//        throw new BBException(sprintf('Class \'%s\' not found', $this->namespace.NAMESPACE_SEPARATOR.$this->classname));

    }

    /**
     * Returns the namespace and the class name to be found.
     *
     * @param string $classpath the absolute class name
     *
     * @return array array($namespace, $classname)
     *
     * @throws \BackBee\AutoLoader\Exception\InvalidNamespaceException Occurs when the namespace is not valid
     * @throws \BackBee\AutoLoader\Exception\InvalidClassnameException Occurs when the class name is not valid
     */
    private function normalizeClassname($classpath)
    {
        $classConfigKey = substr($classpath, strlen($this->classContentNamespace));

        if (NAMESPACE_SEPARATOR == substr($classpath, 0, 1)) {
            $classpath = substr($classpath, 1);
        }

        if (false !== ($pos = strrpos($classpath, NAMESPACE_SEPARATOR))) {
            $namespace = substr($classpath, 0, $pos);
            $classname = substr($classpath, $pos + 1);
        } else {
            $namespace = '';
            $classname = $classpath;
        }

        if (false === preg_match("/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/", $namespace)) {
            throw new Exception\InvalidNamespaceException(sprintf('Invalid namespace provided: %s.', $namespace));
        }

        if (false === preg_match("/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/", $classname)) {
            throw new Exception\InvalidClassnameException(sprintf('Invalid class name provided: %s.', $classname));
        }

        return array($classname, $namespace, $classConfigKey);
    }
}
