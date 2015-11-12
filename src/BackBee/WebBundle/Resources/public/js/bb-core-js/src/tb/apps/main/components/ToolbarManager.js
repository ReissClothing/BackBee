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
        'Core/Renderer',
        'jquery',
        'text!main/tpl/tab-wrapper',
        'jsclass'
    ],
    function (Renderer, jQuery, template) {

        'use strict';

        var ToolbarManager = new JS.Class({

            wrapperId: '#bb5-maintabsContent',
            tabClass: '.tab-pane',

            /**
             * Initialize of ToolbarManager
             */
            initialize: function () {
                return;
            },

            /**
             * Append the html to the toolbar
             * @param {String} id
             * @param {String} html
             * @param {Boolean} force
             */
            append: function (id, html, force) {
                var wrapper = jQuery(this.wrapperId),
                    currentTab = wrapper.children('div#' + id),
                    activeTab = wrapper.children(this.tabClass + '.active');

                activeTab.removeClass('active');

                if (currentTab.length === 0 || force === true) {
                    if (currentTab.length > 0) {
                        currentTab.remove();
                    }

                    wrapper.append(Renderer.render(template, {'id': id, 'html': html}));
                } else {
                    currentTab.addClass('active');
                }
            }
        });

        return new JS.Singleton(ToolbarManager);
    }
);