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

    define(['tb.component/formbuilder/form/element/Element', 'component!logger', 'jsclass'], function (Element, Logger) {
        return {

            load: function (name, req, onload) {
                req(['tb.component/formbuilder/form/element/' + name], function (elementObject) {
                    onload(new JS.Class(Element, elementObject));
                }, function () {
                    Logger.alert("[Formbuilder] Element type not found: " + name);
                });
            }
        };
    });
}());