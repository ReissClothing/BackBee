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
        'Core/ApplicationManager',
        'Core/Renderer',
        'text!page/tpl/contribution/index',
        'text!page/tpl/contribution/scheduling_publication',
        'page.repository',
        'component!formbuilder',
        'component!popin',
        'moment',
        'component!translator',
        'page.save.manager'
    ],
    function (Core,
              jQuery,
              ApplicationManager,
              Renderer,
              template,
              schedulingTemplate,
              PageRepository,
              FormBuilder,
              PopinManager,
              moment,
              translator,
              SaveManager
            ) {

        'use strict';

        /**
         * View of page contribution index
         * @type {Object} Backbone.View
         */
        var PageViewContributionIndex = Backbone.View.extend({

            /**
             * Point of Toolbar in DOM
             */
            el: '#page-contrib-tab',

            mainSelector: Core.get('wrapper_toolbar_selector'),

            schedulingFormTag: '#contribution-scheduling-form',
            schedulingBtnTag: '#contribution-scheduling-btn',
            schedulingTag: '#contribution-scheduling',
            schedulingSubmitTag: '#contribution-scheduling-submit',
            schedulingStateTag: '#contribution-scheduling-state',

            dialogContainerTag: '.bb5-dialog-container',

            formErrorTag: '.form_error',
            publishingElementTag: '.element_publishing',

            /**
             * Initialize of PageViewContributionIndex
             */
            initialize: function (config) {
                this.currentPage = config.data;
            },

            /**
             * Events of view
             */
            bindEvents: function () {
                jQuery(this.el).on('change', '#page-state-select', jQuery.proxy(this.manageState, this));
                jQuery(this.el).on('click', '#page-visibility-input', jQuery.proxy(this.manageVisibilityPage, this));
                jQuery(this.el).on('click', '#contribution-clone-page', jQuery.proxy(this.manageClone, this));
                jQuery(this.el).on('click', '#contribution-edit-page', jQuery.proxy(this.manageEdit, this));
                jQuery(this.el).on('click', '#contribution-delete-page', jQuery.proxy(this.manageDelete, this));
                jQuery(this.el).on('click', this.schedulingBtnTag, jQuery.proxy(this.manageSchedulingPublication, this));
                jQuery(this.el).on('click', '#contribution-seo-page', jQuery.proxy(this.manageSeo, this));
            },

            manageVisibilityPage: function (event) {
                SaveManager.addToSave('is_hidden', !event.currentTarget.checked);
            },

            /**
             * Change the state of the page
             * @param {Object} event
             */
            manageState: function (event) {
                var self = jQuery(event.currentTarget),
                    optionSelected = self.children('option:selected');

                SaveManager.addToSave('state', optionSelected.val());
            },

            /**
             * Edit the page
             */
            manageEdit: function () {
                var config = {
                        'callbackAfterSubmit': this.afterSubmitHandler,
                        'page_uid': Core.get('page.uid'),
                        'from_page': true
                    };

                ApplicationManager.invokeService('page.main.editPage', config);
            },

            afterSubmitHandler: function (data) {

                if (data.parent_uid !== undefined) {
                    PageRepository.find(data.uid).done(function (page) {
                        window.location = page.uri;
                    });

                    return;
                }

                if (this.layoutHasChanged()) {
                    location.reload();
                }
            },

            /**
             * Clone the page
             * @param {Object} event
             */
            manageClone: function () {
                ApplicationManager.invokeService('page.main.clonePage', {'page_uid': this.currentPage.uid});
            },

            /**
             * Delete the page
             * @param {Object} event
             */
            manageDelete: function () {
                ApplicationManager.invokeService('page.main.deletePage', {'uid': this.currentPage.uid, 'doRedirect': true});
            },

            /**
             * On click, build the form and show him in the popin
             * @returns
             */
            manageSchedulingPublication: function () {
                var self = this,
                    config = {
                        elements: {
                            publishing: {
                                label: translator.translate('publication_scheduled_for'),
                                type: 'datetimepicker',
                                placeholder: 'dd/mm/aaaa',
                                template: 'src/tb/apps/page/templates/elements/scheduling-input.twig',
                                value: this.getStateSchedulingAsString(this.currentPage.publishing)
                            },
                            archiving: {
                                label: translator.translate('archiving_scheduled_for'),
                                type: 'datetimepicker',
                                placeholder: 'dd/mm/aaaa',
                                template: 'src/tb/apps/page/templates/elements/scheduling-input.twig',
                                value: this.getStateSchedulingAsString(this.currentPage.archiving)
                            }
                        },
                        form: {
                            submitLabel: 'Ok'
                        },
                        onSubmit: jQuery.proxy(self.onSubmitSchedulingPublication, self),
                        onValidate: jQuery.proxy(self.onValidateSchedulingPublication, self)
                    };

                if (jQuery(this.schedulingTag).length === 0) {

                    jQuery(this.dialogContainerTag).html(Renderer.render(schedulingTemplate));

                    FormBuilder.renderForm(config).done(function (html) {
                        jQuery(self.schedulingTag).html(html);

                        jQuery(self.schedulingTag).dialog({
                            position: { my: "left top", at: "left+270 bottom+2", of: jQuery("#bb5-maintabsContent") },
                            width: 334,
                            height: 120,
                            autoOpen: false,
                            resizable: false,
                            appendTo: self.mainSelector + " .bb5-dialog-container",
                            dialogClass: "ui-dialog-no-title ui-dialog-pinned-to-banner"
                        });

                        Core.Scope.subscribe('page', function () {
                            return;
                        }, function () {
                            jQuery(self.schedulingTag).dialog('close');
                        });

                        jQuery(self.schedulingTag).dialog('open');
                    });

                } else {
                    if (jQuery(this.schedulingTag).dialog('isOpen')) {
                        jQuery(this.schedulingTag).dialog('close');
                    } else {
                        jQuery(this.schedulingTag).dialog('open');
                    }
                }

                // dispatch click events on datepicker icons
                jQuery(self.schedulingTag).on('click', '#btn-publishing', jQuery.proxy(
                    function () {
                        jQuery('#publishing').trigger('click');
                    },
                    self
                ));
                jQuery(self.schedulingTag).on('click', '#btn-archiving', jQuery.proxy(
                    function () {
                        jQuery('#archiving').trigger('click');
                    },
                    self
                ));
            },

            onSubmitSchedulingPublication: function (data, form) {
                var key,
                    date,
                    formObject = jQuery('#' + form.id),
                    elementWrapper = formObject.find(this.publishingElementTag);

                elementWrapper.find(this.formErrorTag).addClass('hidden');

                for (key in data) {
                    if (data.hasOwnProperty(key)) {
                        date = new Date(data[key]);

                        if (isNaN(date.getTime())) {
                            delete data[key];
                        } else {
                            data[key] = date.getTime() / 1000;
                            if (data[key] === parseInt(this.currentPage[key], 10)) {
                                delete data[key];
                            } else {
                                SaveManager.addToSave(key, data[key]);
                            }
                        }
                    }
                }

                if (!jQuery.isEmptyObject(data)) {
                    this.setStateScheduling(data);
                }

                jQuery(this.schedulingTag).dialog('close');
            },

            onValidateSchedulingPublication: function (form, data) {
                var publishingDate,
                    archivingDate;

                if (data.publishing.length > 0 && data.archiving.length > 0) {
                    publishingDate = new Date(data.publishing);
                    archivingDate = new Date(data.archiving);

                    if (publishingDate.getTime() > archivingDate.getTime()) {
                        form.addError('publishing', translator.translate('publishing_before_archiving'));
                    }
                }
            },

            /**
             * Get the correcly format of date in string for datetimepicker
             * @param {number} value
             *
             */
            getStateSchedulingAsString: function (value) {
                var timestamp,
                    day = '';

                if (value !== undefined && value !== null) {
                    timestamp = moment.unix(value);
                    day = timestamp.format('YYYY/MM/DD HH:mm');
                }

                return day;
            },

            /**
             * Set the sheduling statut in contribution index as string
             * @param {Object} config
             */
            setStateScheduling: function (config) {
                var day,
                    state;

                if (config.hasOwnProperty('publishing') && config.publishing !== '' && config.publishing !== null) {
                    day = moment.unix(config.publishing);
                    state = translator.translate('from') + ' ' + day.format('DD/MM/YYYY HH:mm');
                }

                if (config.hasOwnProperty('archiving') && config.archiving !== '' && config.archiving !== null) {
                    day = moment.unix(config.archiving);
                    state += ' ' + translator.translate('till') + ' ' + day.format('DD/MM/YYYY HH:mm');
                }

                jQuery(this.schedulingStateTag).html(state);
            },

            /**
             * Get the metadata of page, build form and show in popin
             */
            manageSeo: function () {

                var self = this,
                    popinTopPosition = jQuery('#' + Core.get('menu.id')).height(),
                    popin = PopinManager.createPopIn({
                        position: { my: "center top", at: "center top+" + popinTopPosition}
                    });

                popin.setTitle(translator.translate('page_seo'));
                popin.display();
                popin.mask();

                Core.Scope.subscribe('page', function () {
                    return;
                }, function () {
                    popin.hide();
                });


                PageRepository.getMetadata(this.currentPage.uid).done(function (metadata) {
                    FormBuilder.renderForm(self.buildConfigSeoForm(metadata, popin)).done(function (html) {
                        popin.setContent(html);
                        popin.unmask();
                    });
                });

            },

            /**
             * Compute SEO data for compatibility with REST
             * @param {Object} data
             * @returns {Object}
             */
            computeSeoData: function (data) {
                var key,
                    newKey,
                    delimiter,
                    value,
                    isDefault = true,
                    result = {};

                for (key in data) {
                    if (data.hasOwnProperty(key)) {
                        value = data[key];
                        delimiter = key.indexOf('__');
                        isDefault = true;

                        if (-1 !== delimiter) {
                            newKey = key.substring(delimiter + 2);
                            key = key.substring(0, delimiter);
                            isDefault = false;
                        }

                        if (!result.hasOwnProperty(key)) {
                            result[key] = {};
                        }

                        if (isDefault === true) {
                            result[key].content = value;
                        } else {
                            result[key][newKey] = value;
                        }
                    }
                }

                return result;
            },

            /**
             * Build config of SEO form with REST data
             * @param {Object} metadata
             * @param {Object} popin
             * @returns {Object}
             */
            buildConfigSeoForm: function (metadata, popin) {
                var self = this,
                    key,
                    value,
                    config = {},
                    meta;

                config.onSubmit = function (data) {
                    popin.mask();
                    PageRepository.setMetadata(self.currentPage.uid, self.computeSeoData(data)).done(function () {
                        popin.unmask();
                        popin.hide();
                    });
                };

                config.elements = {};
                for (key in metadata) {
                    if (metadata.hasOwnProperty(key)) {
                        meta = metadata[key];
                        if (meta.hasOwnProperty('content')) {
                            config.elements[key] = {
                                label: key,
                                type: 'textarea',
                                value: meta.content
                            };

                            for (value in meta) {
                                if (meta.hasOwnProperty(value)) {
                                    if (value !== 'content') {
                                        config.elements[key + '__' + value] = {
                                            label: value,
                                            type: 'textarea',
                                            value: meta[value],
                                            group: key
                                        };

                                        config.elements[key].label = 'content';
                                        config.elements[key].group = key;
                                    }
                                }
                            }
                        }
                    }
                }

                return config;
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {Object} PageViewContributionIndex
             */
            render: function () {
                var self = this;

                PageRepository.getWorkflowState(this.currentPage.layout_uid).done(function (workflowStates) {
                    jQuery(self.el).html(Renderer.render(template, {'page': self.currentPage, 'states': workflowStates}));
                    self.bindEvents();
                    self.setStateScheduling(self.currentPage);
                }).fail(function (e) {
                    console.log(e);
                });
            }
        });

        return PageViewContributionIndex;
    }
);
