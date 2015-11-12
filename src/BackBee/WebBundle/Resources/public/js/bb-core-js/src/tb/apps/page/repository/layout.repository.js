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
        'jquery',
        'jsclass'
    ],
    function (CoreDriverHandler, jQuery) {

        'use strict';

            /**
             * Page repository class
             * @type {Object} JS.Class
             */
        var LayoutRepository = new JS.Class({

                TYPE: 'layout',

                initialize: function () {
                    this.layouts = {};
                },

                find: function (uid) {
                    var dfd = jQuery.Deferred(),
                        layout = this.layouts[uid],
                        self = this;

                    if (undefined !== layout) {
                        dfd.resolve(layout);
                    } else {
                        CoreDriverHandler.read('layout', {'uid': uid}).done(function (layout) {
                            self.addLayout(layout);
                            dfd.resolve(layout);
                        });
                    }

                    return dfd.promise();
                },

                findLayouts: function (site_uid) {
                    var dfd = jQuery.Deferred(),
                        self = this;

                    CoreDriverHandler.read('layout', {'site_uid': site_uid}).done(function (layouts) {
                        var key;

                        for (key in layouts) {
                            if (layouts.hasOwnProperty(key)) {
                                self.addLayout(layouts[key]);
                            }
                        }

                        dfd.resolve(layouts);
                    });

                    return dfd.promise();
                },

                addLayout: function (layout) {
                    var uid = layout.uid;

                    if (undefined === uid) {
                        return;
                    }

                    if (undefined === this.layouts[uid]) {
                        this.layouts[uid] = layout;
                    }
                }
            });

        return new JS.Singleton(LayoutRepository);
    }
);
