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

namespace BackBee\CoreDomainBundle\AutoLoader;

//use BackBee\Exception\BBException;
use BackBee\CoreDomainBundle\Event\Event;
use BackBee\CoreDomainBundle\Stream\ClassWrapper\Exception\ClassWrapperException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

if (false === defined('NAMESPACE_SEPARATOR')) {
    define('NAMESPACE_SEPARATOR', '\\');
}

/**
 * AutoLoader implements an autoloader for BackBee5.
 *
 * It allows to load classes that:
 *
 * * use the standards for namespaces and class names
 * * throw defined wrappers returning php code
 *
 * Classes from namespace part can be looked for in a list
 * of wrappers and/or a list of locations.
 *
 * Beware of the auloloader begins to look for class throw the
 * defined wrappers then in the provided locations
 *
 * Example usage:
 *
 *     $autoloader = new \BackBee\AutoLoader\AutoLoader();
 *
 *     // register classes throw wrappers
 *     $autoloader->registerStreamWrapper('BackBee\ClassContent',
 *                                        'bb.class',
 *                                        '\BackBee\Stream\ClassWrapper\YamlWrapper');
 *
 *     // register classes by namespaces
 *     $autoloader->registerNamespace('BackBee', __DIR__)
 *                ->registerNamespace('Symfony', __DIR__.DIRECTORY_SEPARATOR.'vendor');
 *
 *     // activate the auloloader
 *     $autoloader->register();
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class AutoLoader
{
    /**
     * Current BackBee application.
     *
     * @var \BackBee\BBApplication
     */
    private $_application;

    /**
     * Availables wrappers to resolve loading.
     *
     * @var array
     */
    private $_availableWrappers;

    /**
     * Extensions to include searching file.
     *
     * @var array
     */
    private $_includeExtensions = array('.php');

    /**
     * Namespaces locations.
     *
     * @var array
     */
    private $_namespaces;

    /**
     * Namespaces wrappers.
     *
     * @var array
     */
    private $_streamWrappers;

    /**
     * Is the namespace registered ?
     *
     * @var Boolean
     */
    private $_registeredNamespace = false;

    /**
     *  Events disptacher.
     *
     * @var \BackBee\Event\Dispatcher
     */
    private $_dispatcher;

    /**
     * define if autolader is already restored by container or not.
     *
     * @var boolean
     */
    private $_is_restored;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Class constructor.
     *
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->_availableWrappers = stream_get_wrappers();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Looks for class definition throw declared wrappers according to the namespace.
     *
     * @param string $namespace the namespace's class
     * @param string $classname the class name looked for
     *
     * @return Boolean TRUE if the class is found FALSE else
     *
     * @throws \BackBee\Stream\ClassWrapper\Exception\ClassWrapperException Occurs when the wrapper can not build PHP code
     * @throws \BackBee\AutoLoader\Exception\SyntaxErrorException           Occurs when the generated PHP code is not valid
     */
    private function autoloadThrowWrappers($namespace, $classname)
    {
        if (false === is_array($this->_streamWrappers)) {
            return false;
        }

        foreach ($this->_streamWrappers as $n => $wrappers) {
            if (0 === strpos($namespace, $n)) {
                $classpath = str_replace(array($n, NAMESPACE_SEPARATOR), array('', DIRECTORY_SEPARATOR), $namespace);
                if (DIRECTORY_SEPARATOR == substr($classpath, 0, 1)) {
                    $classpath = substr($classpath, 1);
                }

                foreach ($wrappers as $wrapper) {
                    try {
                        @include sprintf('%s://%s/%s', $wrapper['protocol'], $classpath, $classname);

                        return true;
                    } catch (ClassWrapperException $e) {
                        // The class wrapper cannot return a valid class
                        throw $e;
                    } catch (\RuntimeException $e) {
                        // The include php file is not valid
                        throw new Exception\SyntaxErrorException($e->getMessage(), null, $e->getPrevious());
//                    } catch (BBException $e) {
//                     @TODO gvf why silence the exception??
//                    } catch (\Exception $e) {
//                         Nothing to do
                    }
                }

                $this->_registeredNamespace = true;
            }
        }

        return false;
    }

    /**
     * Looks for class definition using the PHP 5.3 standards to the namespace.
     *
     * @param string $namespace the namespace's class
     * @param string $classname the class name looked for
     *
     * @return Boolean TRUE if the class is found FALSE else
     *
     * @throws \BackBee\AutoLoader\Exception\SyntaxErrorException Occurs when the found PHP code is not valid
     */
    private function autoloadThrowFilesystem($namespace, $classname)
    {
        if (false === is_array($this->_namespaces)) {
            return false;
        }

        $pathfiles = array();
        if (false === is_array($this->_includeExtensions)
                || 0 == count($this->_includeExtensions)) {
            $pathfiles[] = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.$classname;
        } else {
            foreach ($this->_includeExtensions as $ext) {
                $pathfiles[] = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.$classname.$ext;
            }
        }

        foreach ($this->_namespaces as $n => $paths) {
            if (strpos($namespace, $n) === 0) {
                foreach ($paths as $path) {
                    foreach ($pathfiles as $pathfile) {
                        $filename = $path.DIRECTORY_SEPARATOR.$pathfile;
                        if (false === file_exists($filename)) {
                            $filename = $path.DIRECTORY_SEPARATOR.str_replace(str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $n), '', $pathfile);
                        }

                        if (true === file_exists($filename) && true === is_readable($filename)) {
                            try {
                                include_once $filename;

                                return true;
                            } catch (\RuntimeException $e) {
                                // The include php file is not valid
                                throw new Exception\SyntaxErrorException($e->getMessage(), null, $e->getPrevious());
                            }
                        }
                    }
                }

                $this->_registeredNamespace = true;
            }
        }

        return false;
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

        return array($namespace, $classname);
    }

    /**
     * Registers pre-defined stream wrappers.
     */
    private function registerStreams()
    {
        foreach ($this->_streamWrappers as $wrappers) {
            foreach ($wrappers as $wrapper) {
                if (false === in_array($wrapper['protocol'], $this->_availableWrappers)) {
                    stream_wrapper_register($wrapper['protocol'], $wrapper['classname']);
                    $this->_availableWrappers = stream_get_wrappers();
                }
            }
        }
    }

    /**
     * Looks for the class name, call back function for spl_autolad_register()
     * First using the defined wrappers then throw filesystem.
     *
     * @param string $classpath
     *
     * @throws \BackBee\AutoLoader\Exception\ClassNotFoundException Occurs when the class can not be found
     */
    public function autoload($classpath)
    {
        $this->_registeredNamespace = false;

        list($namespace, $classname) = $this->normalizeClassname($classpath);

        if ($this->autoloadThrowWrappers($namespace, $classname)) {
            if (NAMESPACE_SEPARATOR == substr($classpath, 0, 1)) {
                $classpath = substr($classpath, 1);
            }

            if (is_subclass_of($classpath, 'BackBee\CoreDomain\ClassContent\AbstractClassContent')) {
                $event = new Event(new $classpath());
                $this->eventDispatcher->dispatch('classcontent.include', $event);
            }

            return;
        }

        if (true === $this->_registeredNamespace) {
            throw new Exception\ClassNotFoundException(sprintf('Class %s%s%s not found.', $namespace, NAMESPACE_SEPARATOR, $classname));
        }
    }

    /**
     * Returns the wrappers registered for provided namespaces and protocols.
     *
     * @param string|array $namespace The namespaces to look for
     * @param string|array $protocol  The protocols to use
     *
     * @return array An array of wrappers registered for these namespaces and protocols
     */
    public function getStreamWrapperClassname($namespace, $protocol)
    {
        $namespace = (array) $namespace;
        $protocol = (array) $protocol;

        $result = array();
        foreach ($this->_streamWrappers as $ns => $wrappers) {
            if (true === in_array($ns, $namespace)) {
                foreach ($wrappers as $wrapper) {
                    if (true === in_array($wrapper['protocol'], $protocol)) {
                        $result[] = $wrapper['classname'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns AClassContent whom classname matches the provided pattern.
     *
     * @param string $pattern The pattern to test
     *
     * @param  string      $pattern The pattern to test
     * @return array|FALSE An array of classnames matching the pattern of FALSE if none found
     */
    public function glob($pattern)
    {
        // $pattern = 'Media'.DIRECTORY_SEPARATOR.'*'
        $wrappers = $this->getStreamWrapperClassname('BackBee\CoreDomain\ClassContent', 'bb.class');
        if (0 == count($wrappers)) {
            return false;
        }

        $classnames = array();
        foreach ($wrappers as $classname) {
            $wrapper = new $classname();
            if (false !== $matchingclass = $wrapper->glob($pattern)) {
                $classnames = array_merge($classnames, $matchingclass);
            }
        }

        if (0 == count($classnames)) {
            return false;
        }

        return array_unique($classnames);
    }

    /**
     * Registers this auloloader.
     *
     * @param Boolean $throw   [optional] This parameter specifies whether
     *                         spl_autoload_register should throw exceptions
     *                         when the autoload_function cannot be registered.
     * @param Boolean $prepend [optional] If TRUE, spl_autoload_register will
     *                         prepend the autoloader on the autoload stack
     *                         instead of appending it.
     *
     * @return \BackBee\AutoLoader\AutoLoader The current instance of the autoloader class
     */
    public function register($throw = true, $prepend = false)
    {
        spl_autoload_register(array($this, 'autoload'), $throw, $prepend);

        return $this;
    }

    /**
     * Registers stream wrappers.
     *
     * @param string $namespace The namespace
     * @param string $protocol  The wrapper's protocol
     * @param string $classname The class name implementing the wrapper
     *
     * @return \BackBee\AutoLoader\AutoLoader The current instance of the autoloader class
     */
    public function registerStreamWrapper($namespace, $protocol, $classname)
    {
        if (!isset($this->_namespaces[$namespace])) {
            $this->_namespaces[$namespace] = array();
        }

        $this->_streamWrappers[$namespace][] = array('protocol' => $protocol, 'classname' => $classname);
        ksort($this->_streamWrappers);

        $this->registerStreams();

        return $this;
    }
}
