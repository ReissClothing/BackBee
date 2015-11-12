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
        'Core/ApplicationManager',
        'jquery',
        'Core/Renderer',
        'text!bundle/tpl/index'
    ],
    function (ApplicationManager, jQuery, Renderer, template) {

        'use strict';

        /**
         * View of bundle's index
         * @type {Object} Backbone.View
         */
        var BundleViewIndex = Backbone.View.extend({

            id: 'bundle-tab',

            /**
             * Initialize of BundleViewIndex
             * The default key is used for show the first bundle in index
             * @param {Object} config
             * @param {String} defaultKey
             */
            initialize: function (config, defaultKey) {

                if (config.bundles !== undefined) {
                    var currentBundle = config.bundles[0];

                    if (defaultKey !== undefined) {
                        currentBundle = config.bundles[defaultKey];
                    }

                    this.bundle = currentBundle;
                }

                this.force = (config.force === true);
                this.doBinding = (config.bindEvents === true);
            },

            /**
             * Events of the view
             */
            bindEvents: function () {
                var listElement = jQuery('#' + this.id + ' .btn-dialog-extension'),
                    activeElement = jQuery('#' + this.id + ' div.activation-btn-group a'),
                    adminElement = jQuery('#' + this.id + ' .btn-bundle-admin');

                listElement.off('click').on('click', this.doListDialog);
                activeElement.off('click').on('click', this.doExtensionActivation);
                if (0 !== adminElement.length) {
                    adminElement.off('click').on('click', this.openAdmin.bind(this));
                }
            },

            openAdmin: function () {
                ApplicationManager.invokeService('bundle.main.admin', this.bundle);
            },

            /**
             * Show or Hide a dialog with bundle list
             */
            doListDialog: function () {
                var dialog = jQuery('#extensions');

                if (!dialog.get(0)) {
                    ApplicationManager.invokeService('bundle.main.list');
                } else if (dialog.get(0) && !dialog.dialog('isOpen')) {
                    dialog.dialog('open');
                } else {
                    dialog.dialog('close');
                }
            },

            /**
             * Enable or disable the current bundle
             * @param {Object} event
             */
            doExtensionActivation: function (event) {
                var self = jQuery(event.currentTarget),
                    bundleId = self.parent().attr('data-bundle-id'),
                    enable = self.hasClass('enable');
                if (bundleId) {
                    ApplicationManager.invokeService('bundle.main.confirm', event.currentTarget, bundleId, enable);
                }
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {Object} BundleViewIndex
             */
            render: function () {
                var self = this;

                ApplicationManager.invokeService('main.main.toolbarManager').done(function (Service) {
                    Service.append(self.id, Renderer.render(template, {'bundle': self.bundle}), self.force);

                    if (self.doBinding === true) {
                        self.bindEvents();
                    }
                });

                return this;
            }
        });

        return BundleViewIndex;
    }
);