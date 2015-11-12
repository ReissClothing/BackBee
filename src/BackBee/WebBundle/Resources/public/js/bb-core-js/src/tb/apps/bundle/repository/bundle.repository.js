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
define(
    [
        'Core/DriverHandler',
        'Core/RestDriver',
        'jquery',
        'jsclass'
    ],
    function (CoreDriverHandler, CoreRestDriver, jQuery) {

        'use strict';

        /**
         * Bundle repository class
         * @type {Object} JS.Class
         */
        var BundleRepository = new JS.Class({

            TYPE: 'bundle',

            DEFAULT_DRIVER_KEY: 'rest',

            /**
             * Initialize of Bundle repository
             */
            initialize: function () {
                CoreDriverHandler.addDriver(this.DEFAULT_DRIVER_KEY, CoreRestDriver);
            },

            /**
             * List bundles
             * @param {Function} callback
             */
            list: function () {
                var self = this,
                    dfd = jQuery.Deferred();

                if (this.bundleList === undefined) {

                    CoreDriverHandler.read(this.TYPE).done(function (data) {
                        self.bundleList = data;
                        dfd.resolve(self.bundleList);
                    });
                } else {
                    dfd.resolve(this.bundleList);
                }

                return dfd.promise();
            },

            listManageable: function () {
                var dfd = jQuery.Deferred();

                this.list().done(function (bundles) {
                    var key,
                        manageableBundles = [];

                    for (key in bundles) {
                        if (bundles.hasOwnProperty(key)) {
                            if (bundles[key].admin_entry_point) {
                                manageableBundles.push(bundles[key]);
                            }
                        }
                    }

                    dfd.resolve(manageableBundles);
                });

                return dfd.promise();
            },

            /**
             * Set the activation of bundle
             * @param {Boolean} active
             * @param {String} bundleId
             */
            active: function (active, bundleId) {
                return CoreDriverHandler.patch(this.TYPE, {'enable': active}, {'id': bundleId});
            }
        });

        return new JS.Singleton(BundleRepository);
    }
);
