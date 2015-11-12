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
        'Core/Renderer',
        'BackBone',
        'jquery'
    ],
    function (Core, Renderer, Backbone, jQuery) {
        'use strict';

        var ContentView = Backbone.View.extend({

            mainSelector: Core.get('wrapper_toolbar_selector'),

            initialize: function (template, formTag, element) {
                this.el = formTag;
                this.template = template;
                this.element = element;
                this.editClass = 'edit';
                this.elementSelector = 'form#' + this.el + ' ul[data-uid=' + this.element.value + ']';

                this.bindEvents();
            },

            bindEvents: function () {
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.editClass, jQuery.proxy(this.onUpdateClick, this));
            },

            onUpdateClick: function (event) {
                var target = jQuery(event.currentTarget),
                    ul = target.parents('ul');

                Core.ApplicationManager.invokeService('content.main.getContentManager').done(function (ContentManager) {
                    var content = ContentManager.buildElement({'uid': ul.attr('data-uid'), 'type': ul.attr('data-type')});

                    Core.ApplicationManager.invokeService('content.main.getEditionWidget').done(function (Edition) {
                        var config = {
                            onSave: function () {
                                content.getData(undefined, false, true).done(function () {
                                    var img = ul.find('.bb-content-img'),
                                        title = ul.find('.bb-content-form-edit');

                                    if (img.length > 0) {
                                        img.attr('src', content.data.image);
                                    }

                                    if (title.length > 0) {
                                        title.text(content.data.label.substring(0, 5));
                                    }
                                });
                            }
                        };

                        Edition.show(content, config);
                    });
                });
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {String} html
             */
            render: function () {
                return Renderer.render(this.template, {element: this.element});
            }
        });

        return ContentView;
    }
);