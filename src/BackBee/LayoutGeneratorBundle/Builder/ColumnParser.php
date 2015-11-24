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

namespace BackBee\LayoutGeneratorBundle\Builder;

/**
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ColumnParser
{
    private $columnDecorator;

    public function __construct()
    {
        $this->columnDecorator = new ColumnCompatibilityDecorator();
    }

    public function parse($column)
    {
        $column['mainZone'] = $this->getBooleanValue('mainZone', $column);
        $column['inherited'] = $this->getBooleanValue('inherited', $column);
        $column['accept'] = $this->getAccept($column);
        $column['maxentry'] = $this->getMaxEntry($column);
        $column['defaultClassContent'] = $this->getDefault($column);

        $column = $this->columnDecorator->decorate($column);

        return $column;
    }

    private function getBooleanValue($key, $column, $default = false)
    {
        return array_key_exists($key, $column) ? (boolean)$column[$key] : $default;
    }

    private function getAccept($column)
    {
        if (array_key_exists('accept', $column)) {
            if ($column['accept'] === null) {
                return [''];
            }
            return (array)$column['accept'];
        }
        return [''];
    }

    private function getMaxEntry($column)
    {
        if (array_key_exists('maxentry', $column)) {
            if ($column['maxentry'] === null) {
                return 0;
            }
            return (int)$column['maxentry'];
        }
        return 0;
    }

    private function getDefault($column)
    {
        $key = 'defaultClassContent';
        return array_key_exists($key, $column) ? $column[$key] : null;
    }
}
