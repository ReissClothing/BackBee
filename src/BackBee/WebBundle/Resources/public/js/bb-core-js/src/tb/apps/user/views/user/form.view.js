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
    ['require', 'Core', 'jquery'],
    function (require, Core, jQuery) {
        'use strict';

        var trans = Core.get('trans') || function (value) {return value; },
            mainPopin,
            popin;

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            popin_config: {
                width: 250,
                top: 180,
                close: function () {
                    mainPopin.popinManager.destroy(popin);
                }
            },

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data, action, id) {
                var self = this,
                    form;

                mainPopin = data.popin;
                this.user = data.user;
                if (id !== null) {
                    this.popin_config.id = id;
                }
                this.popin = mainPopin.popinManager.createSubPopIn(mainPopin.popin, this.popin_config);
                popin = this.popin;

                if ('edit' === action) {
                    popin.setTitle(data.user.username() + ' ' + trans('edition').toLowerCase());
                } else {
                    popin.setTitle(trans('create_user'));
                }

                form = require('user/form/' + action + '.user.form');
                form.construct(self, data.errors);
            },

            display: function () {
                this.dfd = jQuery.Deferred();
                popin.display();
                return this.dfd.promise();
            },

            destruct: function () {
                this.zone.html('');
            }
        });
    }
);
