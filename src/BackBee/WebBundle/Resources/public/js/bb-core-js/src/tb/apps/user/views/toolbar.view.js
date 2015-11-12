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
    ['require', 'jquery', 'Core/Renderer', 'Core', 'text!user/templates/toolbar.twig'],
    function (require, jQuery, Renderer, Core) {
        'use strict';

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            /**
             * Initialize of PageViewEdit
             */
            initialize: function () {
                var self = this;
                self.zone = jQuery('#bb5-maintabsContent');
                self.tpl = Renderer.render(require('text!user/templates/toolbar.twig'));
                Core.ApplicationManager.invokeService('main.main.toolbarManager').done(function (Service) {
                    Service.append('bb-user-app', Renderer.render(self.tpl), true);
                    self.bindAction();
                });
            },

            newUser: function () {
                Core.ApplicationManager.invokeService('user.user.new', Core.get('application.user').popin, 'new-user-subpopin');
            },

            searchUsers: function (event) {
                var values = jQuery(this).serializeArray(),
                    params = {};

                event.preventDefault();

                values.forEach(function (data) {
                    if (data.name !== 'activated' && data.value !== '') {
                        params[data.name] = data.value;
                    } else if (data.name === 'activated' && data.value !== 'all') {
                        params[data.name] = data.value;
                    }
                });

                Core.ApplicationManager.invokeService('user.user.index', Core.get('application.user').popin, params);
            },

            resetSearchUsers: function (event) {
                event.preventDefault();
                var inputs = jQuery(this).parents('form:first').find('input');
                jQuery.each(inputs, function (index) {
                    var input = jQuery(inputs.get(index));

                    input.val('');
                });

                jQuery(this).parents('form:first').find('select').val('all');
                Core.ApplicationManager.invokeService('user.user.index', Core.get('application.user').popin, {reset: true});
            },

            bindAction: function () {
                this.zone.find('#toolbar-new-user-action').click(this.newUser);
                this.zone.find('#bb-toolbar-user-search-form').submit(this.searchUsers);
                this.zone.find('#bb-toolbar-user-reset-search-form').click(this.resetSearchUsers);
            },

            destruct: function () {
                this.zone.html('');
            }
        });
    }
);
