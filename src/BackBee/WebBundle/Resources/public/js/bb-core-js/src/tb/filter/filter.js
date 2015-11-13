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
(function () {
    'use strict';
    require.config({
        paths: {
            'tb.filter': 'src/tb/filter'
        }
    });

    define(['Core/Renderer'], function (Renderer) {
        return {
            load: function (name, req, onload) {

                var types = ['filter', 'function'];

                req(['tb.filter/' + name + '/main'], function (filter) {
                    var filterFuncName;

                    if ('function' === typeof filter.func &&
                            filter.name !== undefined &&
                            types.indexOf(filter.type) !== -1
                            ) {

                        filterFuncName = 'add' + filter.type.charAt(0).toUpperCase() + filter.type.slice(1);

                        Renderer[filterFuncName](filter.name, filter.func, filter.async || false);

                        onload(filter.func);
                    } else {
                        onload(null);
                    }
                });
            }
        };
    });
}());