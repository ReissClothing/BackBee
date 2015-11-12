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
    'app.content/components/dnd/CommonDnD',
    [
        'Core',
        'jsclass'
    ],
    function (Core) {

        'use strict';

        return new JS.Class({

            bindEvents: function (Manager) {
                Core.Mediator.subscribe('on:classcontent:dragover', this.onDragOver, Manager);
                Core.Mediator.subscribe('on:classcontent:dragend', this.endDragAction, Manager);
                Core.Mediator.subscribe('on:classcontent:dragenter', this.onDragEnter, Manager);
                Core.Mediator.subscribe('on:classcontent:dragleave', this.onDragLeave, Manager);
            },

            unbindEvents: function () {
                Core.Mediator.unsubscribe('on:classcontent:dragover', this.onDragOver);
                Core.Mediator.unsubscribe('on:classcontent:dragend', this.endDragAction);
                Core.Mediator.unsubscribe('on:classcontent:dragenter', this.onDragEnter);
                Core.Mediator.unsubscribe('on:classcontent:dragleave', this.onDragLeave);
            },

            /**
             * Event trigged on drag over dropzone
             * @param {Object} event
             */
            onDragOver: function (event) {
                if (event.target.getAttribute('dropzone')) {
                    event.preventDefault();
                }
            },

            onDragEnter: function (event) {
                if (event.target.getAttribute('dropzone')) {
                    event.target.classList.add('over');
                }
            },

            onDragLeave: function (event) {
                if (event.target.getAttribute('dropzone')) {
                    event.target.classList.remove('over');
                }
            },

            /**
             * Event trigged on drag end content
             * @param {Object} event
             * @returns {Boolean}
             */
            endDragAction: function (event) {
                event.stopPropagation();

                this.resetDataTransfert();
                this.cleanHTMLZoneForContentset();
                this.removeScrollZones();

                return false;
            }
        });
    }
);