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
        'require',
        'Core',
        'jquery',
        'page.repository',
        'page.form',
        'tb.component/formsubmitter/elements/nodeSelector',
        'component!translator',
        'component!popin',
        'component!formbuilder',
        'component!notify'
    ],
    function (require, Core, jQuery, PageRepository, PageForm, nodeSelectorValidator, translator) {

        'use strict';

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        var PageViewEdit = Backbone.View.extend({

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (config) {
                if (typeof config.page_uid !== 'string') {
                    Core.exception('MissingPropertyException', 500, 'Property "page_uid" must be set to constructor');
                }

                this.config = config;
                this.chosenLayout = null;
                this.fromPage = config.from_page || false;

                this.page_uid = this.config.page_uid;
                this.callbackAfterSubmit = this.config.callbackAfterSubmit;

                this.popin = require('component!popin').createPopIn({
                    position: { my: "center top", at: "center top+" + jQuery('#' + Core.get('menu.id')).height()}
                });

                this.formBuilder = require('component!formbuilder');

                Core.ApplicationManager.invokeService('page.main.registerPopin', 'pageEdit', this.popin);
            },

            onSubmit: function (data, form) {
                var self = this,
                    nodes;

                if (this.page_uid !== undefined) {
                    data.uid = this.page_uid;
                }

                if (true === this.fromPage) {
                    nodes = nodeSelectorValidator.compute('move_to', data.move_to, form);
                    if (nodes !== null) {
                        data.parent_uid = nodes[0].pageUid;
                    }

                    delete data.move_to;
                }

                this.popin.mask();
                PageRepository.save(data).done(function (result, response) {

                    if (typeof self.callbackAfterSubmit === 'function') {
                        data.uid = self.page_uid;
                        self.callbackAfterSubmit(data, response, result);
                    }

                    self.popin.unmask();
                    self.popin.hide();
                }).fail(function (error) {
                    require('component!notify').error(error);

                    self.popin.unmask();
                    self.popin.hide();
                });
            },

            onValidate: function (form, data) {
                if (!data.hasOwnProperty('title') || data.title.trim().length === 0) {
                    form.addError('title', translator.translate('title_is_required'));
                }

                if (data.title.trim().length < 4) {
                    form.addError('title', translator.translate('title_must_contain_at_least_3_characters'));
                }

                if (!data.hasOwnProperty('layout_uid') || data.layout_uid.trim().length === 0) {
                    form.addError('layout_uid', translator.translate('template_is_required'));
                }
            },

            handleLayoutChange: function (formRender) {
                var self = this,
                    layoutField = jQuery(formRender).find(".element_layout_uid").eq(0),
                    previousLayout = layoutField.find("select").eq(0).val();
                layoutField.on("change", "select", (function (currentLayout) {
                    return function () {
                        self.chosenLayout = (currentLayout !== jQuery(this).val()) ? jQuery(this).val() : null;
                    };
                }(previousLayout)));
            },

            layoutHasChanged: function () {
                return this.chosenLayout ? true : false;
            },
            /**
             * Render the template into the DOM with the ViewManager
             * @returns {Object} PageViewEdit
             */
            render: function () {

                var self = this;

                this.popin.setTitle(translator.translate('edit_page'));
                this.popin.display();
                this.popin.mask();

                PageForm.edit(this.page_uid, this.fromPage).done(function (configForm) {

                    configForm.onSubmit = jQuery.proxy(self.onSubmit, self);
                    configForm.onValidate = self.onValidate;

                    self.formBuilder.renderForm(configForm).done(function (html) {
                        Core.Mediator.subscribeOnce("on:form:render", self.handleLayoutChange.bind(self));
                        self.popin.setContent(html);
                        self.popin.unmask();
                    });
                });

                return this;
            }
        });

        return PageViewEdit;
    }
);
