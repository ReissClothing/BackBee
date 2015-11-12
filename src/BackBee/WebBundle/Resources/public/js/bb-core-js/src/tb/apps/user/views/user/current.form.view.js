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
    ['require', 'Core', 'jquery', 'component!popin'],
    function (require, Core, jQuery) {
        'use strict';

        var trans = Core.get('trans') || function (value) {return value; },
            popinManager,
            popin;

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            popin_config: {
                id: 'new-user-subpopin',
                width: 250,
                top: 180,
                close: function () {
                    popinManager.destroy(popin);
                }
            },

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data, action) {
                var self = this,
                    form;

                popinManager = require('component!popin');
                this.popin = popinManager.createPopIn({id: 'current-user-popin'});
                this.user = data.user;
                popin = this.popin;

                if ('password' === action) {
                    this.popin.setTitle(trans('password_edit'));
                } else {
                    this.popin.setTitle(trans('account_edit'));
                }

                form = require('user/form/' + action + '.user.form');
                form.construct(self, data.errors);
            },

            display: function () {
                this.dfd = jQuery.Deferred();
                popin.display();
                return this.dfd.promise();
            },

            destroy: function () {
                popinManager.destroy(this.popin);
            }
        });
    }
);