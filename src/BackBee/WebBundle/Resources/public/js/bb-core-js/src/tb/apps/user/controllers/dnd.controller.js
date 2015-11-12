/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

define(['Core', 'jquery'], function (Core, jQuery) {
    'use strict';

    return {
        dragStart: function (event) {
            var target = jQuery(event.target);

            if (target.hasClass('open')) {
                target.removeClass('open');
            }

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text', 'user-add-user');

            this.user =  target.attr('data-user');
            this.inDropZone = false;
            this.leave = 0;
            this.enter = 0;
        },

        dragEnter: function (event) {
            this.enter = this.enter + 1;
            if (undefined !== event &&
                    event.target.getAttribute('dropzone')) {

                event.target.classList.add('ui-state-hover');
                this.inDropZone = true;
                this.group = event.target.getAttribute('data-group');
            }
        },

        dragLeave: function (event) {
            this.leave = this.leave + 1;
            if (this.leave === this.enter) {
                this.inDropZone = false;
                this.group = 0;
                this.leave = 0;
                this.enter = 0;
                event.target.classList.remove('ui-state-hover');
            }
        },

        dragOver: function (event) {
            if (undefined !== event &&
                    true === this.inDropZone) {

                event.preventDefault();
            }
        },

        drop: function (event) {
            if (undefined !== event) {
                event.stopPropagation();
                event.preventDefault();
            }

            if (0 !== this.group && 0 !== this.user && true === this.inDropZone) {
                Core.ApplicationManager.invokeService(
                    'user.user.addGroup',
                    this.popin,
                    this.user,
                    this.group
                );
            }
        },

        dragEnd: function () {
            this.user = 0;
            this.group = 0;
            this.inDropZone = false;
        }
    };
});
