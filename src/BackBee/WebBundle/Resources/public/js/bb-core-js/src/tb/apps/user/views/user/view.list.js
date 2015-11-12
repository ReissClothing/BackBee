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
    ['require', 'Core/Renderer', 'user/entity/user', 'text!user/templates/user/list.item.twig'],
    function (require, renderer, User) {
        'use strict';

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            /**
             * Initialize of UserViewList
             */
            initialize: function (data) {
                this.current = data.current;
                this.group_listing = data.group_listing;
                this.user = new User();
                this.user.populate(data.user);
                this.user.groups().forEach(function (group, key) {
                    this.user.groups()[key] = group.name;
                }, this);
            },

            /**
             * Render the template into the DOM with the ViewManager
             * @returns {Object} PageViewEdit
             */
            render: function () {
                return renderer.render(require('text!user/templates/user/list.item.twig'), {user: this.user, current: this.current, group_listing: this.group_listing});
            }
        });
    }
);