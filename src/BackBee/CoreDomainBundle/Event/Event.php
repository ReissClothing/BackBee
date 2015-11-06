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

namespace BackBee\CoreDomainBundle\Event;

use Symfony\Component\EventDispatcher\Event as sfEvent;

/**
 * A generic class of event in BB application.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Event extends sfEvent
{
    /**
     * The target entity of the event.
     *
     * @var mixed
     */
    protected $target;

    /**
     * Optional arguments passed to the event.
     *
     * @var mixed
     */
    protected $args;

    /**
     * Class constructor.
     *
     * @param mixed $target    The target of the event
     * @param mixed $eventArgs The optional arguments passed to the event
     */
    public function __construct($target, $eventArgs = null)
    {
        $this->target = $target;
        $this->args = $eventArgs;
    }

    /**
     * Returns the target of the event, optionally checks the class of the target.
     *
     * @param type $classname The optional class name to checks
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException Occures on invalid type of target
     *                                   according to the waited class name
     */
    public function getTarget($classname = null)
    {
        if (null === $classname || true === $this->isTargetInstanceOf($classname)) {
            return $this->target;
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid target: expected `%s`, `%s` provided.',
            $classname,
            'object' === gettype($this->target) ? get_class($this->target) : $this->target
        ));
    }

    /**
     * Checks if the target is of this class or has this class as one of its parents.
     *
     * @param string $classname The class name
     *
     * @return bool TRUE if the object is of this class or has this class as one of
     *              its parents, FALSE otherwise
     */
    public function isTargetInstanceOf($classname)
    {
        return is_object($this->target) ? $this->target instanceof $classname : false;
    }

    /**
     * Get argument by key.
     *
     * @param string $key     Key.
     * @param mixed  $default default value to return
     *
     * @return mixed Contents of array key.
     */
    public function getArgument($key, $default = null)
    {
        if ($this->hasArgument($key)) {
            return $this->args[$key];
        }

        return $default;
    }

    /**
     * Add argument to event.
     *
     * @param string $key   Argument name.
     * @param mixed  $value Value.
     *
     * @return GenericEvent
     */
    public function setArgument($key, $value)
    {
        $this->args[$key] = $value;

        return $this;
    }

    /**
     * Has argument.
     *
     * @param string $key Key of arguments array.
     *
     * @return boolean
     */
    public function hasArgument($key)
    {
        return is_array($this->args) && array_key_exists($key, $this->args);
    }

    /**
     * Return the arguments passed to the event.
     *
     * @return mixed|null
     */
    public function getEventArgs()
    {
        return $this->args;
    }
}
