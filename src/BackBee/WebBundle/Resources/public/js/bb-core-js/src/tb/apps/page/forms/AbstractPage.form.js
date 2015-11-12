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
        'jquery',
        'page.repository',
        'component!translator',
        'layout.repository',
        'jsclass'
    ],
    function (jQuery, PageRepository, translator, LayoutRepository) {
        'use strict';

        var Form = new JS.Class({

            form: {
                title: {
                    type: 'text',
                    label: translator.translate('title')
                },
                alttitle: {
                    type: 'text',
                    label: translator.translate('alt_title')
                },
                target: {
                    type: 'select',
                    label: translator.translate('target_page'),
                    options: {
                        '_self': '_self',
                        '_blank': '_blank'
                    }
                },
                url: {
                    type: 'text',
                    label: translator.translate('url'),
                    disabled: true
                },
                move_to: {
                    type: 'nodeSelector',
                    label: translator.translate('move_to_page'),
                    value: [],
                    max_entry: 1
                },
                redirect: {
                    type: 'text',
                    label: translator.translate('redirect_to')
                },
                state: {
                    type: 'hidden',
                    label: translator.translate('page_status')
                }
            },

            getLayoutsObject: function () {
                var dfd = jQuery.Deferred(),
                    self = this,
                    layout_uid = {
                        type: 'select',
                        label: translator.translate('layout'),
                        options: {}
                    };

                PageRepository.findCurrentPage().done(function (page) {
                    LayoutRepository.findLayouts(page.site_uid).done(function (data) {
                        layout_uid.options = self.computeLayouts(data);
                        dfd.resolve(layout_uid);
                    });
                });

                return dfd.promise();
            },

            clear: function () {
                this.form.title.value = '';
                this.form.alttitle.value = '';
                this.form.target.value = [];
                this.form.url.value = [];
                this.form.redirect.value = '';
                this.form.state.value = '';
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

            getPage: function (page_uid) {
                return PageRepository.find(page_uid);
            },

            map: function (object, config) {
                var key,
                    element;

                for (key in object) {
                    if (object.hasOwnProperty(key)) {
                        element = object[key];
                        if (config.hasOwnProperty('elements')) {
                            if (config.elements.hasOwnProperty(key)) {
                                config.elements[key].value = element;
                            }
                        }
                    }
                }

                config.object = object;

                return config;
            }
        });

        return Form;
    }
);
