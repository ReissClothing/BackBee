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

define('tb.component/popin/main', ['Core', 'tb.component/popin/PopIn', 'jquery', 'component!translator', 'jqueryui', 'jsclass'], function (Core, PopIn, jQuery, Translator) {
    'use strict';

    /**
     * Contains every default options for a pop-in
     * @type {Object}
     */
    var DEFAULT_POPIN_OPTIONS = {
            autoOpen: false,
            maxHeight: jQuery(window).height() - 40,
            maxWidth: jQuery(window).height() - 40
        },

        /**
         * PopInManager allow us to handle with ease Core.PopIn
         */
        PopInManager = new JS.Class({

            /**
             * Unique id generator for pop-in id
             * @type {Number}
             */
            popInId: 0,

            /**
             * @type {String}
             */
            toolbarId: Core.get('wrapper_toolbar_selector'),

            /**
             * @type {String}
             */
            containerId: 'bb5-dialog-container',

            /**
             * Create a Core.PopIn with basic configuration like its id and options
             * @param  {Object} config configuration of pop-in
             * @return {PopIn}
             */
            createPopIn: function (config) {
                var popIn = new PopIn(),
                    options = jQuery.extend({}, DEFAULT_POPIN_OPTIONS),
                    option = null,
                    id;

                config = typeof config === 'object' ? config : {};
                id = config.id;

                if (id === undefined) {
                    this.popInId = this.popInId + 1;
                    id = 'pop-in-' + this.popInId;
                } else {
                    delete config.id;
                }

                popIn.setId(id);

                for (option in config) {
                    if (config.hasOwnProperty(option)) {
                        options[option] = config[option];
                    }
                }

                options.appendTo = '#' + this.containerId;

                popIn.setOptions(options);

                return popIn;
            }

        }),

        /**
         * The unique instance of PopInManager
         * @type {PopInManager}
         */
        manager = new PopInManager();

    return {

        /**
         * Initialize the PopInManager by setting the toolbarId
         * @param {String} toolbarId
         */
        init: function (toolbarId) {
            if (typeof toolbarId === 'string') {
                manager.toolbarId = toolbarId;
            }
        },

        /**
         * Create a Core.PopIn with basic configuration like its id and options
         * @param  {Object} options
         * @return {PopIn}
         */
        createPopIn: function (options) {
            var popIn = manager.createPopIn(options),
                self = this;

            if (false === popIn.getOptions().hasOwnProperty('close')) {
                popIn.addOption('close', function () {
                    self.hide(popIn);
                });
            }

            /**
             * Add display() method to Core.PopIn to call Core.PopInManager::display()
             */
            popIn.display = function () {
                self.display(popIn);
            };
            /**
             * Add setContent() method to Core.PopIn
             */
            popIn.setContent = function (content) {
                popIn.content = content;

                if (popIn.isOpen()) {
                    // if the dialog exists, just update the html
                    jQuery('#' + popIn.getId()).html(popIn.getContent());
                }
            };

            /**
             * Add hide() method to Core.PopIn to call Core.PopInManager::hide()
             */
            popIn.hide = function () {
                self.hide(popIn);
            };

            /**
             * Add mask() method to Core.PopIn to display loader overlay on top of dialog
             */
            popIn.mask = function (message) {
                var popInId = '#' + popIn.getId(),
                    element = jQuery('<div/>', {
                        class: 'loader'
                    }).html('<i class="fa fa-spin fa-spinner"></i>' + (message || Translator.translate('loading')));

                element.css('background-color', 'rgba(0, 0, 0, .7)');
                element.css('color', 'white');
                element.css('font-size', '14px');
                element.css('height', '100%');
                element.css('left', '0');
                element.css('line-height', jQuery(popInId).parent().height() + 'px');
                element.css('position', 'absolute');
                element.css('text-align', 'center');
                element.css('top', '0');
                element.css('width', '100%');
                element.find('.fa').css({
                    'color': '#fff',
                    'margin-right': '0.5em'
                });

                jQuery(popInId).parent().find('div.loader').remove();
                element.appendTo(jQuery(popInId).parent());
            };

            /**
             * Add unmask() method to Core.PopIn to remove loader overlay from dialog
             */
            popIn.unmask = function () {
                jQuery('#' + popIn.getId()).parent().find('div.loader').remove();
            };

            return popIn;
        },

        /**
         * Create and add a new child to parent pop-in
         * @param  {PopIn}  parent  the parent of the new pop-in to create
         * @param  {Object} options
         * @return {PopIn}  the child pop-in
         */
        createSubPopIn: function (parent, options) {
            var popIn = this.createPopIn(options);

            if (typeof parent === 'object' && typeof parent.isA === 'function' && parent.isA(PopIn)) {
                parent.addChild(popIn);
                popIn.parent = parent;
            } else {
                throw 'Provided parent is not a PopIn object';
            }

            return popIn;
        },

        /**
         * Display the pop-in
         * @param {PopIn} popIn the pop-in to display
         */
        display: function (popIn) {
            var self = this,
                dialogWrapper,
                parentZIndex;

            if (popIn.isClose()) {
                popIn.open();

                if (jQuery('#' + popIn.getId()).length === 0) {
                    if (null === document.getElementById(manager.containerId)) {
                        jQuery(manager.toolbarId).append('<div id="' + manager.containerId + '"></div>');
                    }

                    if (jQuery('#' + popIn.getId()).length === 0) {
                        jQuery('#' + manager.containerId).append('<div id="' + popIn.getId() + '"></div>');
                    }

                    if (popIn.getContent().length || popIn.getContent() !== '') {
                        jQuery('#' + popIn.getId()).html(popIn.getContent());
                    }

                    jQuery('#' + popIn.getId()).dialog(popIn.getOptions());
                    jQuery('#' + popIn.getId()).on('dialogclose', function () {
                        self.hide(popIn);
                    });

                    jQuery("#" + popIn.getId()).on('dialogfocus', jQuery.proxy(this.handleFocus, this, popIn));

                    jQuery("#" + popIn.getId()).on('dialogopen', function (event) {

                        if (popIn.parent) {
                            parentZIndex = jQuery('#' + popIn.parent.getId()).zIndex();
                            dialogWrapper = jQuery(event.currentTarget).closest(".ui-dialog").eq(0);
                            jQuery(dialogWrapper).zIndex(parentZIndex + 1);
                        }
                    });
                }

                jQuery('#' + popIn.getId()).dialog('open');
            }
        },

        /**
         * Make sure, if the popin is focused on,
         * that its children are still visible
         **/

        handleFocus: function (popIn) {
            var child, children;
            children = popIn.getChildren();
            for (child in children) {
                if (children.hasOwnProperty(child)) {
                    children[child].moveToTop();
                }
            }

        },


        /**
         * Hide pop-in and its children
         * @param {PopIn} popIn
         */
        hide: function (popIn) {
            var child = null,
                children = null;

            if (popIn.isOpen()) {
                popIn.close();
                children = popIn.getChildren();
                jQuery('#' + popIn.getId()).dialog('close');

                for (child in children) {
                    if (children.hasOwnProperty(child)) {
                        this.hide(children[child]);
                    }
                }
            }

        },

        /**
         * Toggle display/hide of pop-in
         * @param {PopIn} popIn the pop-in to toggle
         */
        toggle: function (popIn) {
            if (popIn.isOpen()) {
                this.hide(popIn);
            } else if (popIn.isClose()) {
                this.display(popIn);
            }
        },

        /**
         * Destroy pop-in and its children
         * @param {PopIn} popIn the pop-in to destroy
         */
        destroy: function (popIn) {
            var child = null,
                children = null;

            if (false === popIn.isDestroy()) {
                children = popIn.getChildren();

                if (popIn.isOpen()) {
                    jQuery('#' + popIn.getId()).dialog('close');
                }

                jQuery('#' + popIn.getId()).unbind();
                jQuery('#' + popIn.getId()).remove();

                for (child in children) {
                    if (children.hasOwnProperty(child)) {
                        this.destroy(children[child]);
                    }
                }

                popIn.destroy();
            }
        }

    };

});
