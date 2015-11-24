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
 * Aims to provide a Decorator compatible both with 0.1X and 1.X versions.
 *
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ColumnCompatibilityDecorator
{
    private $values = [
        'layoutSize' => ['height' => 0, 'width' => false],
        'gridSizeInfos' => ['colWidth' => 0, 'gutterWidth' => 0],
        'layoutClass' => 'layoutClass',
        'animateResize' => false,
        'showTitle' => false,
        'target' => 'target',
        'resizable' => true,
        'useGridSize' => true,
        'gridSize' => 5,
        'gridStep' => 12,
        'gridClassPrefix' => 'span',
        'selectedClass' => 'selected',
        'position' => 'none',
        'height' => 0,
        'defaultContainer' => '#container'
    ];

    public function decorate($column)
    {
        foreach ($this->values as $key => $value) {
            $column[$key] = $value;
        }
        $column['id'] = 'Layout__'.(new \DateTime())->format('U').'_1';
        return $column;
    }
}
