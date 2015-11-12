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
    ['require', 'Core/Renderer', 'Core/Utils', 'text!user/templates/group/list.item.twig'],
    function (require, renderer, Utils) {
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
                this.group = data.group;
                this.group.user_count = Utils.castAsArray(data.group.users || []).length;
            },

            /**
             * Render the template into the DOM with the ViewManager
             * @returns {Object} PageViewEdit
             */
            render: function () {
                return renderer.render(require('text!user/templates/group/list.item.twig'), {group: this.group});
            }
        });
    }
);