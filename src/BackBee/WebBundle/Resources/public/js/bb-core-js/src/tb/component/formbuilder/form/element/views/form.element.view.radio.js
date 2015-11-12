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

    var RadioView = Backbone.View.extend({

        initialize: function (template, formTag, element) {
            this.el = formTag;
            this.template = template;
            this.element = element;

            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            Core.Mediator.subscribe('before:form:submit', function (form) {
                if (form.attr('id') === self.el) {
                    var element = form.find('.element_' + self.element.getKey()),
                        input = element.find('input[name="' + self.element.getKey() + '"]:checked'),
                        span = element.find('span.updated'),
                        key,
                        data = [],
                        oldData = self.element.value,
                        updated = false;

                    input.each(function () {
                        var target = jQuery(this);
                        data.push(target.val());
                    });

                    if (oldData.length !== data.length) {
                        updated = true;
                    } else {
                        for (key in data) {
                            if (data.hasOwnProperty(key)) {
                                if (String(data[key]) !== String(oldData[key])) {
                                    updated = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (updated === true) {
                        span.text('updated');
                    } else {
                        span.text('');
                    }
                }
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

    return RadioView;
});