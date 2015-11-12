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
define(['jquery', 'Core/Renderer', 'text!main/tpl/toolbar', 'component!translator'], function (jQuery, Renderer, template, translator) {

    'use strict';

    /**
     * View of bundle's index
     * @type {Object} Backbone.View
     */
    var MainViewIndex = Backbone.View.extend({

        /**
         * Initialize of MainViewIndex
         * @param {Object} config
         */
        initialize: function (config) {
            this.el = config.tbSelector;
            this.toolbar = {
                menus: [
                    {
                        label: translator.translate('user'),
                        text: translator.translate('user_management'),
                        url: '#/user/index',
                        active: false
                    },
                    {
                        label: translator.translate('contribute'),
                        text: translator.translate('edition'),
                        url: '#/contribution/index',
                        active: false
                    },
                    {
                        label: translator.translate('page_management'),
                        text: translator.translate('page_management'),
                        url: '#/page/manage',
                        active: false
                    },
                    {
                        label: translator.translate('bundle'),
                        text: translator.translate('plugins'),
                        url: '#/bundle/index',
                        active: false
                    }
                ],
                logo: require.toUrl('html/img/backbee.png')
            };

        },

        /**
         * Render the template into the DOM with the Renderer
         * @returns {Object} MainViewIndex
         */
        render: function () {
            jQuery(this.el).html(Renderer.render(template, this.toolbar));

            return this;
        }
    });

    return MainViewIndex;
});
