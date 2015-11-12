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

define(['Core', 'Core/Renderer', 'BackBone', 'jquery'], function (Core, Renderer, Backbone, jQuery) {

    'use strict';

    var FormView = Backbone.View.extend({

        el: Core.get('wrapper_toolbar_selector'),

        /**
         * Initialize of FormView
         * @param {String} template
         * @param {Object} elements
         * @param {Object} form
         */
        initialize: function (template, groups, form) {
            this.form_button_class = 'bb-submit-form';
            this.template = template;
            this.groups = groups;
            this.form = form;
            this.bindEvents();
        },

        /**
         * Events of view
         */
        bindEvents: function () {
            jQuery(this.el).on('click', '#' + this.form.getId() + ' .' + this.form_button_class, jQuery.proxy(this.computeForm, this));
        },

        /**
         * Compute the data of form and build Object
         * @param {Object} form
         * @returns {unresolved}
         */
        computeData: function (form) {
            var paramObj = {},
                disabled = form.find(':input:disabled').removeAttr('disabled'),
                formSerialized = form.serializeArray();

            jQuery.each(formSerialized, function (i) {
                if (paramObj.hasOwnProperty(formSerialized[i].name)) {
                    paramObj[formSerialized[i].name] = jQuery.makeArray(paramObj[formSerialized[i].name]);
                    paramObj[formSerialized[i].name].push(formSerialized[i].value);
                } else {
                    paramObj[formSerialized[i].name] = formSerialized[i].value;
                }
            });

            this.computeCheckBoxes(form, paramObj);

            disabled.attr('disabled', 'disabled');

            this.form.onValidate(this.form, paramObj);

            return paramObj;
        },

        computeCheckBoxes: function (form, paramObj) {
            var checkboxes = form.find('input:checkbox');

            if (checkboxes.length > 0) {
                checkboxes.each(function () {
                    var target = jQuery(this),
                        name = target.prop('name');

                    if (!target.prop('checked')) {
                        if (paramObj[name] === undefined) {
                            paramObj[name] = [];
                        }
                    }
                });
            }
        },

        /**
         * Compute the form
         */
        computeForm: function () {
            Core.Mediator.publish('before:form:submit', jQuery('#' + this.form.id));

            var jqueryForm = jQuery('form#' + this.form.id),
                data = this.computeData(jqueryForm);

            if (this.form.isValid()) {
                this.form.onSubmit(data, this.form);
            } else {
                this.replaceForm();
            }
        },

        /**
         * Replace html form if form has errors
         * @param {Object} data
         */
        replaceForm: function () {
            this.form.render(true);
        },

        /**
         * Render the template into the DOM with the Renderer
         * @returns {String} html
         */
        render: function () {
            return Renderer.render(this.template, {groups: this.groups, form: this.form});
        }
    });

    return FormView;
});