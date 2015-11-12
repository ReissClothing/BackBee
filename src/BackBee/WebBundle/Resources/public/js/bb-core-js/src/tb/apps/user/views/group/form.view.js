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
    ['require', 'jquery', 'user/form/group.form'],
    function (require, jQuery) {
        'use strict';

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data) {
                var self = this;

                this.selector = '#toolbar-new-group';
                if (data.group.id !== undefined) {
                    this.selector = '#toolbar-group-' + data.group.id;
                }
                this.group = data.group;

                require('user/form/group.form').construct(this, data.error).then(
                    function (tpl) {
                        self.print(tpl);
                    }
                );
            },

            print: function (tpl) {
                jQuery(this.selector).html(tpl);
            },

            display: function () {
                this.dfd = jQuery.Deferred();
                return this.dfd.promise();
            },

            destruct: function () {
                this.zone.html('');
            }
        });
    }
);