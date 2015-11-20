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
    private $classContentNamespace;
    private $classBuilder;

    /**
     * Class constructor.
     *
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, $classContentNamespace, $classBuilder)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->classContentNamespace = $classContentNamespace;
        $this->classBuilder = $classBuilder;
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
     * Looks for the class name, call back function for spl_autolad_register()
     * First using the defined wrappers then throw filesystem.
     *
     * @param string $classpath
     *
     * @throws \BackBee\AutoLoader\Exception\ClassNotFoundException Occurs when the class can not be found
     */
    public function autoload($classpath)
    {
        if (0 === strpos($classpath, $this->classContentNamespace)) {

            if ($classString = $this->classBuilder->build($classpath)) {
                if (0 == strpos(base64_encode($classString),'PD9waHAKbmFtZXNwYWNlIEJhY2tCZWVcQ29yZURvbWFpblxDbGFzc0NvbnRlbnRcQmxvY2s7CgovKioKICogQFxEb2N0cmluZVxPUk1cTWFwcGluZ1xFbnRpdHkocmVwb3NpdG9yeUNsYXNzPSJCYWNrQmVlXENvcmVEb21haW5CdW5kbGVcQ2xhc3NDb250ZW50XFJlcG9zaXRvcnlcQ2xhc3NDb250ZW50UmVwb3NpdG9yeSIpCiAqIEBcRG9jdHJpbmVcT1JNXE1hcHBpbmdcVGFibGUobmFtZT0iY29udGVudCIpCiAqIEBcRG9jdHJpbmVcT1JNXE1hcHBpbmdcSGFzTGlmZWN5Y2xlQ2FsbGJhY2tzCiAqIAogKiBAcHJvcGVydHkgQmFja0JlZVxDb3JlRG9tYWluXENsYXNzQ29udGVudFxFbGVtZW50XFRleHQgJGJvZHkgcGFyYWdyYXBoIGNvbnRhaW5lcgogKi8KY2xhc3MgQXV0b0Jsb2NrIGV4dGVuZHMgXEJhY2tCZWVcQ29yZURvbWFpblxDbGFzc0NvbnRlbnRcQmxvY2tcQXV0b0Jsb2NrIAp7CiAgICAKICAgIHB1YmxpYyBmdW5jdGlvbiBfX2NvbnN0cnVjdCgkdWlkID0gTlVMTCwgJG9wdGlvbnMgPSBOVUxMKQogICAgewogICAgICAgIHBhcmVudDo6X19jb25zdHJ1Y3QoJHVpZCwgJG9wdGlvbnMpOwogICAgICAgICR0aGlzLT5pbml0RGF0YSgpOwogICAgfQoKICAgIHByb3RlY3RlZCBmdW5jdGlvbiBpbml0RGF0YSgpCiAgICB7CiAgICAgICAgJHRoaXMtPmRlZmluZURhdGEoJ2JvZHknLCAnQmFja0JlZVxDb3JlRG9tYWluXENsYXNzQ29udGVudFxFbGVtZW50XFRleHQnLCBhcnJheSAoCiAgJ2RlZmF1bHQnID0+IAogIGFycmF5ICgKICAgICd2YWx1ZScgPT4gJ1lvdXIgdGV4dCBoZXJlLi4uJywKICApLAogICdsYWJlbCcgPT4gJ3BhcmFncmFwaCBjb250YWluZXInLAogICdtYXhlbnRyeScgPT4gMSwKICAncGFyYW1ldGVycycgPT4gCiAgYXJyYXkgKAogICAgJ3J0ZScgPT4gJ3BhcmFncmFwaCcsCiAgKSwKKSk7CiAgICAgICAgJHRoaXMtPmRlZmluZVBhcmFtKCdkZWx0YScsIGFycmF5ICgKICAndHlwZScgPT4gJ3RleHQnLAogICd2YWx1ZScgPT4gMCwKICAnbGFiZWwnID0+ICdJZ25vcmUgdGhlICJ4IiBmaXJzdCBlbGVtZW50cycsCikpLT5kZWZpbmVQYXJhbSgncGFyZW50X25vZGUnLCBhcnJheSAoCiAgJ3R5cGUnID0+ICdub2RlU2VsZWN0b3InLAogICd2YWx1ZScgPT4gCiAgYXJyYXkgKAogICksCiAgJ2xhYmVsJyA9PiAnUGFnZScsCikpLT5kZWZpbmVQYXJhbSgnc3RhcnQnLCBhcnJheSAoCiAgJ3ZhbHVlJyA9PiAwLAopKS0+ZGVmaW5lUGFyYW0oJ2NvbnRlbnRfdG9fc2hvdycsIGFycmF5ICgKICAndmFsdWUnID0+IAogIGFycmF5ICgKICAgIDAgPT4gJ0JhY2tCZWVcXENvcmVEb21haW5cXENsYXNzQ29udGVudFxcQXJ0aWNsZVxcQXJ0aWNsZScsCiAgKSwKKSktPmRlZmluZVBhcmFtKCdsaW1pdCcsIGFycmF5ICgKICAndHlwZScgPT4gJ3RleHQnLAogICd2YWx1ZScgPT4gMTAsCiAgJ2xhYmVsJyA9PiAnTnVtYmVyIG9mIGVsZW1lbnRzIHRvIGRpc3BsYXknLAopKS0+ZGVmaW5lUGFyYW0oJ211bHRpcGFnZScsIGFycmF5ICgKICAndHlwZScgPT4gJ2NoZWNrYm94JywKICAnb3B0aW9ucycgPT4gCiAgYXJyYXkgKAogICAgJ211bHRpcGFnZScgPT4gJ011bHRpcGFnZScsCiAgKSwKICAndmFsdWUnID0+IAogIGFycmF5ICgKICApLAopKS0+ZGVmaW5lUGFyYW0oJ3JlY3Vyc2l2ZScsIGFycmF5ICgKICAndHlwZScgPT4gJ2NoZWNrYm94JywKICAnb3B0aW9ucycgPT4gCiAgYXJyYXkgKAogICAgJ3JlY3Vyc2l2ZScgPT4gJ1JlY3Vyc2l2ZScsCiAgKSwKICAndmFsdWUnID0+IAogIGFycmF5ICgKICAgIDAgPT4gJ3JlY3Vyc2l2ZScsCiAgKSwKKSk7CiAgICAgICAgJHRoaXMtPmRlZmluZVByb3BlcnR5KCduYW1lJywgJ0F1dG9ibG9jaycpLT5kZWZpbmVQcm9wZXJ0eSgnZGVzY3JpcHRpb24nLCAnQXV0b21hdGVkIGNvbnRlbnQgbGlzdGluZycpLT5kZWZpbmVQcm9wZXJ0eSgnY2F0ZWdvcnknLCBhcnJheSAoCiAgMCA9PiAnIUFydGljbGUnLAopKS0+ZGVmaW5lUHJvcGVydHkoJ2luZGV4YXRpb24nLCBhcnJheSAoCikpOwogICAgICAgIHBhcmVudDo6aW5pdERhdGEoKTsKICAgIH0')){
                $a =1;
                    }
                @include 'data://text/plain;base64,'.base64_encode($classString);

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
}
