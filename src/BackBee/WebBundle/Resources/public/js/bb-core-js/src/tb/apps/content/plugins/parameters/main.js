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
        'component!popin',
        'component!formbuilder',
        'definition.manager',
        'component!formsubmitter',
        'jquery',
        'component!translator',
        'jsclass'
    ],
    function (Core, PluginManager, Popin, FormBuilder, DefinitionManager, FormSubmitter, jQuery, translator) {

        'use strict';

        PluginManager.registerPlugin('parameters', {

            /**
             * Initialization of plugin
             */
            onInit: function () {
                this.createPopin();
            },

            /**
             * Create popin for parameters form
             */
            createPopin: function () {
                this.popin = Popin.createPopIn({
                    close: function () {
                        Core.ApplicationManager.invokeService('content.main.removePopin', 'contentParameters');
                    },
                    position: { my: "center top", at: "center top+" + jQuery('#' + Core.get('menu.id')).height()}
                });
                this.popin.setTitle(translator.translate('parameters'));
                this.popin.addOption('width', '400px');

                Core.ApplicationManager.invokeService('content.main.registerPopin', 'contentParameters', this.popin);
            },

            /**
             * Build form from parameters and show popin
             */
            showParameters: function () {
                var self = this,
                    content = this.getCurrentContent();

                content.getData().done(function () {
                    var parameters = content.getParameters(),
                        config = {
                            elements: self.computeParameters(parameters),
                            onSubmit: jQuery.proxy(self.onSubmit, self)
                        };

                    FormBuilder.renderForm(config).done(function (html) {
                        self.popin.display();
                        self.popin.setContent(html);
                    });
                });
            },

            onSubmit: function (data, form) {
                var self = this,
                    content = this.getCurrentContent();

                FormSubmitter.process(data, form).done(function (res) {

                    self.computeData(res);

                    if (Object.keys(res).length > 0) {
                        content.setParameters(res);

                        Core.ApplicationManager.invokeService('content.main.save').done(function (promise) {
                            promise.done(function () {
                                content.refresh().done(function () {
                                    self.popin.hide();
                                });
                            });
                        });
                    } else {
                        self.popin.hide();
                    }
                });
            },

            computeData: function (data) {
                var key;

                for (key in data) {
                    if (data.hasOwnProperty(key)) {
                        if (data[key] === null) {
                            delete data[key];
                        }
                    }
                }

                return data;
            },

            /**
             * Verify if the plugin can be apply on the context
             * If content don't have parameters, the button is hidden
             * @returns {Boolean}
             */
            canApplyOnContext: function () {
                var content = this.getCurrentContent(),
                    definition = DefinitionManager.find(content.type),
                    result = false;

                if (Object.keys(this.computeParameters(definition.parameters)).length > 0) {
                    result = true;
                }

                return result;
            },

            /**
             * Verify if parameters have the mandatories attributes
             * @param {Object} parameters
             */
            computeParameters: function (parameters) {
                var key,
                    param;

                for (key in parameters) {
                    if (parameters.hasOwnProperty(key)) {
                        param = parameters[key];
                        if (!param.hasOwnProperty('type')) {
                            delete parameters[key];
                        }
                    }
                }

                return parameters;
            },

            /**
             *
             * @returns {Array}
             */
            getActions: function () {
                var self = this;

                return [
                    {
                        name: 'parameters',
                        ico: 'fa fa-cog',
                        label: translator.translate('parameters'),
                        cmd: self.createCommand(self.showParameters, self),
                        checkContext: function () {
                            return true;
                        }
                    }
                ];
            }
        });
    }
);
