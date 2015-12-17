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

namespace BackBee\ApiBundle\Patcher;

use Metadata\MetadataFactoryInterface;

/**
 * RightManager is able to build a mapping of authorized action on entity's properties with
 * the provided Metadata\MetadataFactoryInterface.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RightManager
{
    /**
     * This factory will be used to build authorization mapping.
     *
     * @var \Metadata\MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * mapping of entity namespace and properties authorized actions.
     *
     * @var array
     */
    private $rights;

    /**
     * RightManager's constructor.
     *
     * @param MetadataFactoryInterface $metadataFactory the factory to use to build authorization mapping
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->rights = array();
    }

    /**
     * Return true if the $operation is authorized on $entity's $attribute, else false.
     *
     * @param object $entity
     * @param string $attribute
     * @param string $operation
     *
     * @return boolean true if $operation is authorized, else false
     */
    public function authorized($entity, $attribute, $operation)
    {
        $authorized = true;
        $classname = get_class($entity);
        if (!array_key_exists($classname, $this->rights)) {
            $this->buildRights($classname);
        }

        $attribute = str_replace('/', '', $attribute);
        if (true === $authorized && !array_key_exists($attribute, $this->rights[$classname])) {
            $authorized = false;
        }

        if (true === $authorized && !in_array($operation, $this->rights[$classname][$attribute])) {
            $authorized = false;
        }

        return $authorized;
    }

    /**
     * Add authorization mapping for entity.
     *
     * @param object $entity
     * @param array  $mapping
     *
     * @return self
     */
    public function addAuthorizationMapping($entity, $mapping)
    {
        $classname = get_class($entity);
        if (!isset($this->rights[$classname])) {
            $this->buildRights($classname);
        }

        $this->rights[$classname] = array_merge($this->rights[$classname], $mapping);

        return $this;
    }

    /**
     * Builds the authtorization mapping for the given $classname.
     *
     * @param string $classname
     */
    private function buildRights($classname)
    {
        $metadatas = $this->metadataFactory->getMetadataForClass($classname);
        $reflection = new \ReflectionClass($classname);

        $this->rights[$classname] = array();
        if (null !== $metadatas) {
            foreach ($metadatas->propertyMetadata as $propertyName => $propertyMetadata) {
                $propertyName = $this->cleanPropertyName($propertyName);
                $this->rights[$classname][$propertyName] = array();

                if (
                    false === $propertyMetadata->readOnly
                    && $reflection->hasMethod($this->buildProperMethodName('set', $propertyName))
                ) {
                    $this->rights[$classname][$propertyName][] = 'replace';
                }
            }
        }
    }

    /**
     * This method will replace '_' by '' of $propertyName if its first letter is an underscore (_).
     *
     * @param string $propertyName the property we want to clean
     *
     * @return string cleaned property name
     */
    private function cleanPropertyName($propertyName)
    {
        return preg_replace('#^_([\w_]+)#', '$1', $propertyName);
    }

    /**
     * Builds a valid method name for property name; Replaces every '_' by ''
     * and apply ucfirst to every words seperated by an underscore.
     *
     * @param string $prefix       the prefix to prepend to the method name (example: 'get', 'set', 'is')
     * @param string $propertyName
     *
     * @return string a valid method name
     */
    private function buildProperMethodName($prefix, $propertyName)
    {
        $methodName = explode('_', $propertyName);
        $methodName = array_map(function ($str) {
            return ucfirst($str);
        }, $methodName);

        return $prefix.implode('', $methodName);
    }
}
