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
        'content.pluginmanager',
        'content.manager',
        'component!translator',
        'component!popin',
        'jquery',
        'jsclass'
    ],
    function (Core, PluginManager, ContentManager, Translator, PopinManager, jQuery) {

        'use strict';

        PluginManager.registerPlugin('remove', {

            /**
             * Initialization of plugin
             */
            onInit: function () {
                var self = this;

                this.popin = PopinManager.createPopIn({
                    position: { my: "center top", at: "center top+" + jQuery('#' + Core.get('menu.id')).height()}
                });

                this.popin.setTitle(Translator.translate('remove_content'));
                this.popin.setContent(Translator.translate('remove_content_confirmation_message'));
                this.popin.addButton('Ok', function () {
                    ContentManager.remove(self.getCurrentContent());
                    self.popin.hide();
                });
            },

            /**
             * Remove the content
             */
            remove: function () {
                this.popin.display();
            },

            /**
             * Verify if the plugin can be apply on the context
             * @returns {Boolean}
             */
            canApplyOnContext: function () {
                var parent = this.getCurrentContent().getParent();

                return (parent === null) ? false : parent.isAContentSet();
            },

            /**
             *
             * @returns {Array}
             */
            getActions: function () {
                var self = this;

                return [
                    {
                        name: 'Remove',
                        ico: 'fa fa-times',
                        label: Translator.translate('remove_content'),
                        cmd: self.createCommand(self.remove, self),
                        checkContext: function () {
                            return true;
                        }
                    }
                ];
            }
        });
    }
);