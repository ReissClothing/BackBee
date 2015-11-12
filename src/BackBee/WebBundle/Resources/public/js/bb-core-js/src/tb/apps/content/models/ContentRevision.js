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

    var ContentRevision = new JS.Class({

        /**
         * Initialize Content revision
         *
         * @param {Object} config
         */
        initialize: function (config) {
            this.uid = config.uid;
            this.type = config.type;
        },

        addElement: function (key, element) {
            if (undefined === this.elements) {
                this.elements = {};
            }

            this.elements[key] = element;
        },

        getElement: function (key) {
            var result;

            if (undefined !== this.elements) {
                result = this.elements[key];
            }

            return result;
        },

        setElements: function (elements) {
            this.elements = elements;
        },

        setParameters: function (parameters) {
            if (parameters !== undefined && Object.keys(parameters).length > 0) {
                this.parameters = parameters;
            } else {
                if (this.hasOwnProperty('parameters')) {
                    delete this.parameters;
                }
            }
        },

        addParam: function (key, param) {
            if (undefined === this.parameters) {
                this.parameters = {};
            }

            this.parameters[key] = param;
        },

        isEmpty: function () {
            return this.elements === undefined && this.parameters === undefined;
        }
    });

    return ContentRevision;
});
