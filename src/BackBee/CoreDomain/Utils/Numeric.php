<?php
/*
 * Copyright (c) 2011-2014 Lp digital system
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
 */
namespace BackBee\CoreDomain\Utils;

/**
 * Set of utility methods to deal with numeric varaibles
 *
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Numeric
{
    /**
     * Checks if the variable can be cast to an integer
     * @param  mixed   $var The variable to test
     * @return Boolean TRUE if the variable can be cast to integer, FALSE otherwise
     */
    public static function isInteger($var)
    {
        return is_numeric($var) && (string) ((int) $var) === (string) $var;
    }

    /**
     * Checks if the variable can be cast to a positive integer
     * @param  mixed   $var    The variable to test
     * @param  type    $strict Optional, if TRUE (default) checks for a strictly positive value
     * @return Boolean TRUE if the variable can be cast to a positive integer, FALSE otherwise
     */
    public static function isPositiveInteger($var, $strict = true)
    {
        return self::isInteger($var) && (true === $strict ? (int) $var > 0 : (int) $var >= 0);
    }
}
