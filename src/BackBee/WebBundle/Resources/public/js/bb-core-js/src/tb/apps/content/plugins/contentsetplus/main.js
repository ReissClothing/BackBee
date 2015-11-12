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
        'content.pluginmanager',
        'content.manager',
        'definition.manager',
        'content.widget.DialogContentsList',
        'jquery',
        'component!translator',
        'jsclass'
    ],
    function (PluginManager, ContentManager, DefinitionManager, DialogContentsList, jQuery, Translator) {

        'use strict';

        PluginManager.registerPlugin('contentsetplus', {

            blockClass: '.bb-block',

            /**
             * Initialization of plugin
             */
            onInit: function () {
                return;
            },

            /**
             * Add block into contentset
             *
             * accepts: 0 => show all contents in popin
             * accepts: 1 => add content directly in contentset
             * accepts: > 0 => show all blocks needed
             */
            add: function () {
                var content = this.getCurrentContent(),
                    accepts = content.getDefinition('accept');

                if (accepts.length === 1) {
                    ContentManager.createElement(accepts[0]).done(function (newContent) {
                        content.append(newContent);
                    });
                } else if (accepts.length === 0) {
                    this.showPopin();
                } else {
                    this.showPopin(this.buildContents(accepts));
                }
            },

            /**
             * Build contents with definition and type
             * @param {Object} accepts
             * @returns {Array}
             */
            buildContents: function (accepts) {
                var key,
                    contents = [];

                for (key in accepts) {
                    if (accepts.hasOwnProperty(key)) {
                        contents.push(DefinitionManager.find(accepts[key]));
                    }
                }

                return contents;
            },

            /**
             * Show popin and bind events
             * @param {Mixed} contents
             */
            showPopin: function (contents) {
                var config = {};

                if (this.widget === undefined) {
                    if (contents !== undefined) {
                        config.contents = contents;
                    }
                    this.widget = new DialogContentsList(config);
                }
                this.widget.show();
                this.bindEvents();
            },

            /**
             * Bind events of popin
             */
            bindEvents: function () {
                if (this.binded !== true) {
                    jQuery('#' + this.widget.popin.getId()).on('click', this.blockClass, jQuery.proxy(this.onContentClick, this));

                    this.binded = true;
                }
            },

            /**
             * On content click event
             * On click the content is created and append to contentset
             * @param {Object} event
             * @returns {Boolean}
             */
            onContentClick: function (event) {
                this.widget.hide();

                var self = this,
                    currentContent = this.getCurrentContent(),
                    currentTarget = jQuery(event.currentTarget),
                    img = currentTarget.find('img'),
                    type = img.data('bb-type');

                ContentManager.createElement(type).done(function (content) {
                    var config = self.config,
                        position = 0,
                        currentType = currentContent.type;

                    if (config.hasOwnProperty(currentType)) {
                        if (config[currentType].hasOwnProperty('appendPosition') && config[currentType].appendPosition === 'bottom') {
                            position = 'last';
                        }
                    }
                    currentContent.append(content, position);
                });

                return false;
            },

            /**
             * Verify if the plugin can be apply on the context
             * @returns {Boolean}
             */
            canApplyOnContext: function () {
                return this.getCurrentContent().isAContentSet();
            },

            /**
             * Return the config for shown button
             * and event associated
             * @returns {Array}
             */
            getActions: function () {
                var self = this;

                return [
                    {
                        name: 'Plus',
                        ico: 'fa fa fa-plus',
                        label: Translator.translate('add_item_plus_plugin'),
                        cmd: self.createCommand(self.add, self),
                        checkContext: function () {
                            return true;
                        }
                    }
                ];
            }
        });
    }
);