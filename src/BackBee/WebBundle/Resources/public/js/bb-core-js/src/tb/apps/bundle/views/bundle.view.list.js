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
        'Core',
        'jquery',
        'Core/Renderer',
        'text!bundle/tpl/list',
        'bundle.view.index',
        'jqueryui'
    ],
    function (Core, jQuery, Renderer, template, IndexView) {

        'use strict';

        /**
         * View of bundle's list
         * @type {Object} Backbone.View
         */
        var BundleViewList = Backbone.View.extend({

            /**
             * Point of listing bundles in DOM
             */
            el: '.bb5-dialog-container',

            mainSelector: Core.get('wrapper_toolbar_selector'),

            /**
             * Initialize of BundleViewList
             * @param {Object} config
             */
            initialize: function (config) {
                if (config.hasOwnProperty('bundles')) {
                    this.bundles = config.bundles;
                    this.categories = this.sortBundles(config.bundles);
                }
            },

            /**
             * Events of the view
             */
            events: {
                'click .bb5-data-toggle .bb5-data-toggle-header': 'doToggleHeaderEvent',
                'click .bb5-list-data-item': 'doToggleDataItemEvent'
            },

            /**
             * Show or hide the header of the current item
             * @param {Object} event
             */
            doToggleHeaderEvent: function (event) {
                var self = jQuery(event.currentTarget);
                jQuery('#extensions').find('.bb5-data-toggle.open').not(self.parent()).removeClass('open');
                self.parent().toggleClass('open');
            },

            /**
             * Show the current bundle into the index of bundle (Toolbar)
             * @param {Object} event
             */
            doToggleDataItemEvent: function (event) {
                var self = jQuery(event.currentTarget),
                    bundles = this.bundles,
                    bundleId = self.attr('id'),
                    config = {},
                    key,
                    bundle,
                    currentKey = null,
                    view;

                for (key in bundles) {
                    if (bundles.hasOwnProperty(key)) {
                        bundle = bundles[key];
                        if (bundle.id === bundleId) {
                            currentKey = key;
                            break;
                        }
                    }
                }

                if (currentKey !== null) {
                    config.bundles = this.bundles;
                    config.force = true;
                    config.bindEvents = true;

                    view = new IndexView(config, currentKey);
                    view.doListDialog();
                    view.render();
                }
            },

            /**
             * Sort the list of bundles with them categories.
             * If an bundle don't have category, an default category is created
             * @param {Object} data
             * @returns {Object}
             */
            sortBundles: function (bundles) {
                var key,
                    bundle,
                    category,
                    categoryKey,
                    categoriesArray = {};

                for (key in bundles) {
                    if (bundles.hasOwnProperty(key)) {
                        bundle = bundles[key];
                        if (bundle.hasOwnProperty(category) && bundle.category.length > 0) {
                            for (categoryKey in bundle.category) {
                                if (bundle.category.hasOwnProperty(categoryKey)) {
                                    category = bundle.category[categoryKey];
                                    if (!categoriesArray.hasOwnProperty(category)) {
                                        categoriesArray[category] = [];
                                    }
                                    categoriesArray[category].push(bundle);
                                }
                            }
                        } else {
                            if (!categoriesArray.hasOwnProperty('default')) {
                                categoriesArray.default = [];
                            }
                            categoriesArray.default.push(bundle);
                        }
                    }
                }

                return categoriesArray;
            },

            /**
             * Create the dialog for listing of bundles
             */
            computeDialog: function () {
                var self = this;

                jQuery('#extensions').dialog({
                    position: {my: 'left top', at: 'left bottom+2', of: jQuery('#bb5-maintabsContent')},
                    width: 323,
                    height: 400 > jQuery(window).height() - (20 * 2) ? jQuery(window).height() - (20 * 2) : 400,
                    autoOpen: false,
                    appendTo: self.mainSelector + ' .bb5-dialog-container',
                    dialogClass: 'ui-dialog-no-title ui-dialog-pinned-to-banner'
                });
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {Object} BundleViewList
             */
            render: function () {
                jQuery(this.el).html(Renderer.render(template, {categories: this.categories}));

                this.computeDialog();

                jQuery('#extensions').dialog('open');

                return this;
            }
        });

        return BundleViewList;
    }
);