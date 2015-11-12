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
        'page.view.tree.contribution',
        'jQuery'
    ],
    function (Parent, jQuery) {
        'use strict';

        return Parent.extend({

            deferred: {},

            /**
             * Event trigged on double click in node tree
             * @param {Object} event
             */
            onDoubleClick: function (event) {
                if (event.node.is_fake === true) {
                    return;
                }
                this.deferred.resolve(event.node.uid);
            },

            beforeShow: function () {
                return;
            },

            bindEvents: function () {
                this.treeView.on('tree.dblclick', this.onDoubleClick);
            },

            /**
             * Render the template into the DOM with the ViewManager
             * @returns {Object} PageViewClone
             */
            render: function () {
                this.deferred = new jQuery.Deferred();

                this.view.getTree().done(function (tree) {
                    tree.display();
                });

                return this.deferred.promise();
            }
        });
    }
);