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
define('tb.component/exceptions-viewer/viewer', ['Core'], function (Core) {
    'use strict';

    var template = '<div id="toolbar-count-errors" class="errors">0</div>',

        showExceptions = function () {
            Core.get('errors').forEach(function (error) {
                console.warn(error);
            });
        };

    return function (config) {
        document.getElementsByTagName('body')[0].insertAdjacentHTML('beforeend', template);
        var element = document.getElementById('toolbar-count-errors');
        element.style.position = 'fixed';
        element.style.top = '4px';
        element.style.left = '5px';
        element.style.backgroundColor = 'red';
        element.style.color = 'white';
        element.style.display = 'none';
        element.style.zIndex = 99999999;
        element.style.cursor = 'pointer';
        element.style.padding = '2px 3px';
        element.style.fontWeight = 'bold';

        element.addEventListener('click', showExceptions);

        Core.Mediator.subscribe('api:set:lastError', function () {
            element.innerHTML = parseInt(element.innerHTML, 10) + 1;
            element.style.display = 'block';
            if (config.showInConsole) {
                showExceptions();
            }
        });
    };
});