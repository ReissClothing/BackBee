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
/*global MutationObserver */
define(['Core', 'jquery'], function (Core, jQuery) {
    'use strict';

    var parent,

        mediator = Core.Mediator,

        dnd_process = [
            'dragstart',
            'drag',
            'dragenter',
            'dragleave',
            'dragover',
            'drop',
            'dragend'
        ],


        bindEl = function (el, context, process) {
            (function (element, process, context) {
                element.addEventListener(process, function (event) {
                    mediator.publish('on:' + context + ':' + process, event);
                });
            }(el, process, context));
        },

        defineAs = function (keyword, el, context) {
            var i;
            if (el.dataset && el.dataset.dndAttached === 'true') {
                return;
            }
            if (keyword === 'drag') {
                for (i = 0; i < 3; i = i + 1) {
                    if (i === 2) {
                        i = i + 4;
                    }
                    bindEl(el, context, dnd_process[i]);
                }
            } else {
                for (i = 0; i < 4; i = i + 1) {
                    bindEl(el, context, dnd_process[i + 2]);
                }
            }
            el.dataset.dndAttached = true;
        },

        attachListeners = function (parent, selector, context) {
            var draggable = parent.find(selector + '[draggable="true"]:not([data-dnd-attached="true"])'),
                dropzone = parent.find(selector + '[dropzone="true"]:not([data-dnd-attached="true"])');

            context = context || 'undefined';

            draggable.each(function (key) {
                defineAs('drag', draggable.get(key), context);
            });
            dropzone.each(function (key) {
                defineAs('drop', dropzone.get(key), context);
            });
        },

        delegate = function (selector, context) {
            if (MutationObserver !== undefined) {
                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        attachListeners(jQuery(mutation.target), selector, context);
                    });
                });
                observer.observe(parent.get(0), {childList: true, subtree: true});
            } else {
                parent.on('DOMSubtreeModified', function () {
                    attachListeners(selector, context);
                });
            }
        },

        dnd = {
            defineAsDraggable: function (context, selector) {
                var el = parent.find((selector || '') + '[draggable="true"]');
                context = context || 'undefined';
                defineAs('drag', el, context);
            },

            defineAsDropzone: function (context, selector) {
                var el = parent.find((selector || '') + '[dropzone="true"]');
                context = context || 'undefined';
                defineAs('drop', el, context);
            },

            addListeners: function (context, selector) {
                selector = selector || '';
                delegate(selector, context);
                attachListeners(parent, selector, context);
            }
        };

    return function (selector) {
        parent = jQuery(selector);
        return dnd;
    };
});