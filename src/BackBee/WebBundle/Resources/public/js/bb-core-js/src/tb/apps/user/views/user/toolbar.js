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
    [
        'require',
        'Core/Renderer',
        'user/entity/user',
        'Core',
        'jquery',
        'component!translator',
        'text!user/templates/user/toolbar.twig'
    ],
    function (require, renderer, User, Core, jQuery, Translator) {
        'use strict';

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({
            events: {
                'click .bb-service': 'executeService'
            },

            template: require('text!user/templates/user/toolbar.twig'),

            /**
             * Initialize of UserViewList
             */
            initialize: function (data) {
                this.user = new User();
                this.user.populate(data.user);
            },

            /**
             * Render the template into the DOM with the ViewManager
             * @returns {Object} PageViewEdit
             */
            render: function () {
                var locales = Core.config('component:translator').locales,
                    dataTemplate = {
                        'login': this.user.login(),
                        'locales': locales,
                        'current_locale': Translator.locale
                    };

                this.$el.append(renderer.render(this.template, dataTemplate));

                this.$el.on('click', '.bb-locale-btn', function () {
                    var element = jQuery(this),
                        locale = element.data('locale');


                    if (locale !== Translator.locale) {
                        Translator.setLocale(locale);
                        location.reload();
                    }
                });

                jQuery('.dropdown-toggle').dropdown();
                return this;
            },

            executeService: function (event) {
                var service = jQuery(event.currentTarget).data('service');
                Core.ApplicationManager.invokeService('user.user.' + service, this.user);
            }
        });
    }
);