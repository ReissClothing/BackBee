/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
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

define(
    [
        'jquery',
        'jsclass'
    ],
    function (jQuery) {

        'use strict';

        var SaveManager = new JS.Class({

            /**
             * Return all draft checked
             * @param {String} selector
             * @returns {Array}
             */
            save: function (selector) {
                var self = this,
                    data = jQuery(selector + ' input[data-savable="true"]:checked');

                this.selector = selector;
                this.dataToSave = [];

                data.each(function () {
                    var element = jQuery(this),
                        object = self.buildObject(element);

                    if (object.type !== '') {
                        self.push(object);
                    }
                });

                return this.dataToSave;
            },

            /**
             * Build an object for the save
             *
             * Param -> Search parent and set in parameters array
             * Scalar -> Search parent and set in elements array
             * ContentSet -> Check if element input is checked for save all of elements
             * Other: Minimum attributes are uid and type
             *
             * @param {Object} element
             */
            buildObject: function (element) {

                var parent,
                    elementContentSet,
                    object = {
                        uid: element.data('uid'),
                        type: element.data('type'),
                        elements: []
                    };

                if (element.data('scalar') === true || element.data('param') === true) {

                    parent = this.searchObject(element.data('parent-uid'), true);

                    if (parent !== undefined) {
                        if (element.data('scalar') === true) {
                            parent.elements.push(element.data('name'));
                        } else {
                            if (!parent.hasOwnProperty('parameters')) {
                                parent.parameters = [];
                            }

                            parent.parameters.push(element.data('name'));
                        }
                    }
                } else if (element.data('contentset') === true) {
                    elementContentSet = jQuery(this.selector + ' input[data-parent-id="' + element.data('id') + '"][data-element="true"]');
                    object.elements = elementContentSet.prop('checked');
                }

                return object;
            },

            /**
             * Search element into the dom and build
             *
             * @param {String} uid
             * @returns {undefined|Object}
             */
            buildObjectFromDOM: function (uid) {
                var result,
                    object = jQuery(this.selector + ' input[data-uid="' + uid + '"]');

                if (object.length > 0) {
                    result = this.buildObject(object);
                }

                return result;
            },

            /**
             * Search object from dataToSave list and/or DOM
             *
             * @param {String} uid
             * @param {Boolean} build
             * @returns {undefined|Object}
             */
            searchObject: function (uid, build) {
                var key,
                    object,
                    result;

                for (key in this.dataToSave) {
                    if (this.dataToSave.hasOwnProperty(key)) {
                        object = this.dataToSave[key];
                        if (object.hasOwnProperty('uid')) {
                            if (object.uid === uid) {
                                result = object;
                                break;
                            }
                        }
                    }
                }

                if (build === true && result === undefined) {
                    result = this.buildObjectFromDOM(uid);
                    this.push(result);
                }

                return result;
            },

            /**
             * Verify if object isn't in dataToSave list and
             * Push into dataToSave list
             * @param {Object} object
             */
            push: function (object) {
                if (object !== undefined && object.uid !== undefined) {
                    if (undefined === this.searchObject(object.uid)) {
                        this.dataToSave.push(object);
                    }
                }
            }
        });

        return new JS.Singleton(SaveManager);
    }
);