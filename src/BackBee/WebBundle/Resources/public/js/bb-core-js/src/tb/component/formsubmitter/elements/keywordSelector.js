/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


/*
 * Copyright (c) 2011-2013 Lp digital system
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

define(['jsclass'], function () {

    'use strict';

    var keywordSelector = {

        services: {},

        compute: function (key, value, form) {
            var data = null,
                previousValue = form.elements[key].value;

            if (value !== JSON.stringify(previousValue)) {
                data = JSON.parse(value);
            }
            return data;
        }
    };

    return keywordSelector;
});