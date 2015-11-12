define(
    [
        'Core',
        'require',
        'jquery',
        'page.repository',
        'layout.repository',
        'page.form',
        'component!translator',
        'tb.component/formsubmitter/elements/nodeSelector',
        'component!popin',
        'component!formbuilder',
        'component!formsubmitter',
        'component!notify'
    ],
    function (Core, require, jQuery, PageRepository, LayoutRepository, PageForm, translator, nodeSelectorValidator) {

        'use strict';

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        var PageViewNew = Backbone.View.extend({

            /**
             * Initialize of PageViewNew
             */
            initialize: function (config) {
                this.popin = require('component!popin').createPopIn({
                    position: { my: "center top", at: "center top+" + jQuery('#' + Core.get('menu.id')).height()}
                });

                this.popin.setId(config.popinId);
                this.fromPage = config.from_page || false;

                this.formBuilder = require('component!formbuilder');

                this.config = config;

                this.parent_uid = this.config.parent_uid;
                this.callbackAfterSubmit = this.config.callbackAfterSubmit;

                Core.ApplicationManager.invokeService('content.main.registerPopin', 'newPage', this.popin);
            },

            computeLayouts: function (layouts) {
                var key,
                    layout,
                    data = {'': ''};

                for (key in layouts) {
                    if (layouts.hasOwnProperty(key)) {
                        layout = layouts[key];
                        data[layout.uid] = layout.label;
                    }
                }

                return data;
            },

            onSubmit: function (data, form) {
                var self = this,
                    nodes;

                if (typeof this.parent_uid === 'string') {
                    data.parent_uid = this.parent_uid;
                }

                if (false === this.fromPage) {
                    nodes = nodeSelectorValidator.compute('move_to', data.move_to, form);
                    if (nodes !== null) {
                        data.parent_uid = nodes[0].pageUid;
                    }

                    delete data.move_to;
                }

                delete data.move_to;

                this.popin.mask();
                PageRepository.find(data.parent_uid).done(function (parent) {
                    LayoutRepository.find(parent.layout_uid).done(function (layout) {

                        if (true === layout.is_final) {
                            data.parent_uid = parent.parent_uid;
                        }

                        if (data.parent_uid === undefined) {
                            self.popin.unmask();
                            self.popin.hide();
                            return;
                        }

                        PageRepository.save(data).done(function (data, response) {
                            if (typeof self.callbackAfterSubmit === 'function') {
                                self.callbackAfterSubmit(data, response);
                            }

                            self.popin.unmask();
                            self.popin.hide();
                        }).fail(function (error) {
                            require('component!notify').error(error);

                            self.popin.unmask();
                            self.popin.hide();
                        });
                    });
                });
            },

            onValidate: function (form, data) {
                if (!data.hasOwnProperty('title') || data.title.trim().length === 0) {
                    form.addError('title', translator.translate('title_is_required'));
                }

                if (data.title.trim().length < 3) {
                    form.addError('title', translator.translate('title_must_contain_at_least_3_characters'));
                }

                if (!data.hasOwnProperty('layout_uid') || data.layout_uid.trim().length === 0) {
                    form.addError('layout_uid', translator.translate('template_is_required'));
                }
            },

            /**
             * Render the template into the DOM with the ViewManager
             * @returns {Object} PageViewNew
             */
            render: function () {
                var self = this;

                this.popin.setTitle(translator.translate('create_a_page'));
                this.popin.display();
                this.popin.mask();

                PageForm.new(this.fromPage).done(function (configForm) {

                    configForm.onSubmit = jQuery.proxy(self.onSubmit, self);
                    configForm.onValidate = self.onValidate;

                    PageRepository.find(self.parent_uid).done(function (parent) {
                        LayoutRepository.find(parent.layout_uid).done(function (layout) {
                            if (layout.is_final) {
                                if (undefined === configForm.form) {
                                    configForm.form = {};
                                }
                                configForm.form.information = translator.translate('new_page_final_message');
                            }

                            self.formBuilder.renderForm(configForm).done(function (html) {
                                self.popin.setContent(html);
                                self.popin.unmask();
                            });
                        });
                    });
                });

                return this;
            }
        });

        return PageViewNew;
    }
);
