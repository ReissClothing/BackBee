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
        'content.widget.Edition',
        'component!translator',
        'jsclass'
    ],
    function (
        PluginManager,
        Edition,
        Translator
    ) {

        'use strict';

        PluginManager.registerPlugin('edition', {

            onInit: function () {
                return;
            },

            /**
             * Edit the content
             */
            edit: function () {
                Edition.show(this.getCurrentContent());
            },

            /**
             * Verify if the plugin can be apply on the context
             * @returns {Boolean}
             */
            canApplyOnContext: function () {
                return !this.getCurrentContent().isAContentSet();
            },

            /**
             * Get actions
             * @returns {Array}
             */
            getActions: function () {
                var self = this;

                return [
                    {
                        name: 'Edit',
                        ico: 'fa fa-pencil',
                        label: Translator.translate('edit_content'),
                        cmd: self.createCommand(self.edit, self),
                        checkContext: function () {
                            return true;
                        }
                    }
                ];
            }
        });
    }
);