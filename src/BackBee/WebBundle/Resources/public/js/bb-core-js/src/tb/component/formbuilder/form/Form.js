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
    'tb.component/formbuilder/form/Form',
    [
        'Core',
        'underscore',
        'BackBone',
        'component!translator',
        'jsclass'
    ],
    function (Core, us, Backbone, Translator) {
        'use strict';

        /**
         * Form object
         */
        var Form = new JS.Class({

            AVAILABLE_METHOD: ['POST', 'GET'],

            /**
             * Initialize of Form
             */
            initialize: function (config) {

                us.extend(this, Backbone.Events);

                this.elements = {};
                this.errors = {};

                this.config = config;

                this.computeMandatoryConfig(config);

                this.computeDefaultValue(config);
            },

            /**
             * Verify a mandatory field and set the view and template in form
             * @param {Object} config
             */
            computeMandatoryConfig: function (config) {

                var self = this;

                if (config === undefined) {
                    Core.exception('MissingConfigException', 500, 'Config must be set');
                }

                if (!config.hasOwnProperty('onSubmit')) {
                    config.onSubmit = function () {
                        return;
                    };
                }
                this.onSubmit = config.onSubmit;

                if (!config.hasOwnProperty('onValidate')) {
                    config.onValidate = function () {
                        return true;
                    };
                }
                this.onValidate = function (form, data) {
                    self.resetErrors();
                    config.onValidate(form, data);
                };

                if (!config.hasOwnProperty('template')) {
                    Core.exception('MissingPropertyException', 500, 'Property "template" not found in form');
                }
                this.template = config.template;

                if (!config.hasOwnProperty('view')) {
                    Core.exception('MissingPropertyException', 500, 'Property "view" not found in form');
                }
                this.view = config.view;
            },

            /**
             * Set the default value if is not define in config
             * @param {Object} config
             */
            computeDefaultValue: function (config) {

                this.id = 'form_' + Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);

                this.method = 'POST';
                if (config.hasOwnProperty('method') && us.contains(this.AVAILABLE_METHOD, config.method)) {
                    this.method = config.method;
                }

                this.action = null;
                if (config.hasOwnProperty('action')) {
                    this.action = config.action;
                }

                this.submitLabel = Translator.translate('save');
                if (config.hasOwnProperty('submitLabel')) {
                    this.submitLabel = config.submitLabel;
                }

                this.information = null;
                if (config.hasOwnProperty('information')) {
                    this.information = config.information;
                }

                this.error = null;
                if (config.hasOwnProperty('error')) {
                    this.error = config.error;
                }
            },

            /**
             * Get the method of form
             * @returns {String}
             */
            getMethod: function () {
                return this.method;
            },

            /**
             * Get the action of form
             * @returns {String}
             */
            getAction: function () {
                return this.action;
            },

            /**
             * Get the label of button submit
             * @returns {String}
             */
            getSubmitLabel: function () {
                return this.submitLabel;
            },

            /**
             * Get the unique id
             * @returns {String}
             */
            getId: function () {
                return this.id;
            },

            /**
             * Get the error
             * @returns {String}
             */
            getFormError: function () {
                return this.error;
            },

            /**
             * Add an element into the container
             * @param {String} key
             * @param {Object} element
             * @returns {Object} Form
             */
            add: function (key, element) {
                if (!this.elements.hasOwnProperty(key)) {

                    if (!element.hasOwnProperty('class') || !element.hasOwnProperty('view') || !element.hasOwnProperty('template')) {

                        Core.exception('MissingPropertyException', 500, 'One or more property not found on add element in form for: ' + key);
                    }
                    this.elements[key] = element;
                }

                return this;
            },

            /**
             * Remove an element to the container
             * @param {String} key
             * @returns {Object} Form
             */
            remove: function (key) {
                if (this.elements.hasOwnProperty(key)) {
                    delete this.elements[key];
                }

                return this;
            },

            /**
             * Get the element by this key
             * @param {String} key
             * @returns {Object} Form
             */
            get: function (key) {
                if (this.elements.hasOwnProperty(key)) {
                    return this.elements[key];
                }

                return null;
            },

            /**
             * Get all elements of the container
             * @returns {Object} Form
             */
            getElements: function () {
                return this.elements;
            },

            /**
             * Add error on the input corresponding with the key
             * @param {String} key
             * @param {String} error
             * @returns {Object} Form
             */
            addError: function (key, error) {
                if (!this.errors.hasOwnProperty(key)) {
                    this.errors[key] = error;
                }

                return this;
            },

            /**
             * Return the error of the input corresponding
             * @param {String} key
             * @returns {String}
             */
            getError: function (key) {
                return this.errors[key];
            },

            resetErrors: function () {
                this.errors = {};

                return this;
            },

            isValid: function () {
                return Object.getOwnPropertyNames(this.errors).length === 0;
            },

            /**
             * Render each element in form
             * @returns {String} HTML
             */
            render: function (showError) {
                var key,
                    items = {},
                    View,
                    template,
                    elementConfig,
                    ElementClass,
                    element,
                    group,
                    elementView,
                    elementTemplate;

                View = require(this.view);
                template = require(this.template);

                for (key in this.elements) {
                    if (this.elements.hasOwnProperty(key)) {

                        if (showError === true) {
                            element = this.getElementRendered(key);
                            if (null !== element) {
                                element.setError(this.getError(key));
                            }
                        } else {
                            elementConfig = this.elements[key];

                            ElementClass = require(elementConfig.class);
                            elementTemplate = require(elementConfig.template);
                            elementView = require(elementConfig.view);

                            element = new ElementClass(key, elementConfig, this.id, elementView, elementTemplate, this.getError(key));

                            if (this.elementsRendered === undefined) {
                                this.elementsRendered = [];
                            }

                            this.elementsRendered.push(element);
                            group = element.group;

                            if (!items.hasOwnProperty(group)) {
                                items[group] = [];
                            }

                            items[group].push(element);
                        }
                    }
                }

                if (showError !== true) {
                    return new View(template, items, this).render();
                }
            },

            getElementRendered: function (key) {
                var i,
                    element,
                    res = null;

                for (i in this.elementsRendered) {
                    if (this.elementsRendered.hasOwnProperty(i)) {
                        element = this.elementsRendered[i];
                        if (element.getKey() === key) {
                            res = element;
                            break;
                        }
                    }
                }

                return res;
            }
        });

        return Form;
    }
);
