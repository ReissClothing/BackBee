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
        'content.repository',
        'Core/Renderer',
        'component!popin',
        'text!content/tpl/block_display',
        'jquery',
        'content.dnd.manager',
        'component!translator',
        'jqueryui',
        'jsclass'
    ],
    function (Core, ContentRepository, Renderer, Popin, blockDisplayTpl, jQuery, DndManager, Translator) {

        'use strict';

        var DialogContentsList = new JS.Class({

            toggleClasses: '.bb5-data-toggle .bb5-data-toggle-header',

            initialize: function (config) {
                this.computeConfig(config);
                this.createPopin();
            },

            /**
             * Create popin for parameters form
             */
            createPopin: function () {
                this.popin = Popin.createPopIn({
                    open: function () {
                        var popin = jQuery(this);
                        popin.parent('.ui-dialog:first').css({
                            top: ((window.innerHeight / 2) - (popin.height() / 2)),
                            left: 20
                        });

                    }
                });

                Core.ApplicationManager.invokeService('content.main.registerPopin', 'blockDisplay', this.popin);
                this.popin.setTitle(Translator.translate('block_display'));
            },

            /**
             * Set the default data for config
             * @param {Object} config
             */
            computeConfig: function (config) {
                if (config === undefined) {
                    config = {};
                }

                this.contents = null;
                if (config.hasOwnProperty('contents')) {
                    if (typeof config.contents === 'object') {
                        this.contents = config.contents;
                    }
                }

                this.categories = null;
                if (config.hasOwnProperty('categories')) {
                    if (typeof config.categories === 'object') {
                        this.categories = config.categories;
                    }
                }

                this.draggable = false;
                if (config.hasOwnProperty('draggable') && config.draggable === true) {
                    this.draggable = true;
                }
            },

            /**
             * Manage shown of popin
             * No data: search all categories and set to this
             */
            show: function () {
                if (!this.shown) {
                    var self = this;

                    if (this.categories === null && this.contents === null) {
                        ContentRepository.findCategories().done(function (categories) {
                            self.categories = categories;
                            self.doShow();
                        });
                    } else {
                        this.doShow();
                    }

                    this.shown = true;
                } else {
                    this.popin.display();
                }
            },

            /**
             * Build fake categories when contents is set
             * @param {Object} contents
             * @returns {Array}
             */
            buildFakeCategories: function (contents) {
                var category = {};

                category.name = 'Blocks';
                category.show = true;
                category.contents = contents;

                return [category];
            },

            /**
             * Show the popin acording to data
             */
            doShow: function () {
                var config = {
                        draggable: true,
                        id: this.popin.getId()
                    },
                    html;

                if (this.categories !== null) {
                    config.categories = this.categories;
                } else if (this.contents !== null) {
                    config.categories = this.buildFakeCategories(this.contents);
                }

                html = Renderer.render(blockDisplayTpl, config);

                this.popin.setContent(html);

                this.popin.display();
                DndManager.attachDnDOnPalette();

                this.bindEvents();
            },

            /**
             * Bind events into popin
             */
            bindEvents: function () {
                jQuery('#' + this.popin.getId()).on(
                    'click',
                    this.toggleClasses,
                    jQuery.proxy(this.toggleHeader, this)
                );
            },

            /**
             * On click event
             * On category's click, show contents of this.
             * @param {Object} event
             */
            toggleHeader: function (event) {
                var currentTarget = jQuery(event.currentTarget);

                jQuery('#' + this.popin.getId())
                    .find('.bb5-data-toggle.open')
                    .not(currentTarget.parent())
                    .removeClass('open');

                currentTarget.parent().toggleClass('open');
            },

            /**
             * Hide the popin
             */
            hide: function () {
                this.popin.hide();
            }
        });

        return DialogContentsList;
    }
);
