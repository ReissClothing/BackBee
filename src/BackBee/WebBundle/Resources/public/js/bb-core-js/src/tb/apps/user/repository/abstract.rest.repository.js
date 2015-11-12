
/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

define(
    ['Core/DriverHandler', 'Core/RestDriver', 'jquery', 'jsclass'],
    function (CoreDriverHandler, CoreRestDriver, jQuery) {
        'use strict';

        return new JS.Class({

            class_name: '',

            mandatories_attribute: [],

            rest_version: '1',

            identifier: 'uid',

            /**
             * Delete the page
             * @param {String} identifier
             * @returns {Promise}
             */
            delete: function (identifier) {
                return CoreDriverHandler.delete(this.class_name, {'id': identifier});
            },

            /**
             * Get the page by identifier
             * @param {String} identifier
             */
            find: function (identifier) {
                return CoreDriverHandler.read(this.class_name, {'id': identifier});
            },

            /**
             * Search pages
             *
             * @param array filters
             * @param {int} start
             * @param {int} count
             * @param {Function} callback
             */
            findBy: function (filters) {
                var dfd =  jQuery.Deferred();
                filters = filters || {};

                CoreDriverHandler.read(this.class_name, filters).then(
                    function (data) {
                        dfd.resolve(data);
                    },
                    function (e) {
                        dfd.reject(e);
                    }
                );

                return dfd.promise();
            },

            paginate: function (filters, start, count) {
                filters = filters || {};

                if (start !== undefined && start !== null) {
                    filters.start = start;
                }

                if (count !== undefined && count !== null) {
                    filters.count = count;
                }

                return this.findBy(filters);
            },

            /**
             * Initialize of Page repository
             */
            initializeRestDriver: function () {
                CoreDriverHandler.addDriver('rest', CoreRestDriver);
            },

            /**
             * Verify if the method is put method with a mandatories attributes array
             * @param {Object} data
             * @returns {Boolean}
             */
            isPutMethod: function (data) {
                var key,
                    mandatory;

                for (key = 0; key < this.mandatories_attribute.length; key = key + 1) {
                    mandatory = this.mandatories_attribute[key];
                    if (!data.hasOwnProperty(mandatory)) {
                        return false;
                    }
                }

                return true;
            },

            createSavePromise: function (action, data, identifier) {
                var dfd =  jQuery.Deferred();
                CoreDriverHandler[action](this.class_name, data, identifier).then(
                    function (data) {
                        dfd.resolve(data);
                    },
                    function (e) {
                        dfd.reject(e);
                    }
                );

                return dfd.promise();
            },

            /**
             * Save the page with a correctly method
             * @param {Object} data
             * @returns {Promise}
             */
            save: function (data) {
                var identifier,
                    promise;

                if (data.hasOwnProperty(this.identifier)) {
                    identifier = data[this.identifier];
                    delete data[this.identifier];

                    if (this.isPutMethod(data)) {
                        promise = this.createSavePromise('update', data, {'id': identifier});
                    } else {
                        promise = this.createSavePromise('patch', data, {'id': identifier});
                    }
                } else {
                    promise = this.createSavePromise('create', data);
                }

                return promise;
            }
        });
    }
);
