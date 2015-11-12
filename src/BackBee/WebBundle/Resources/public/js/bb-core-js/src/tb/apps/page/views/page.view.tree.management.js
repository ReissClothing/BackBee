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

define(['Core', 'page.view.tree.contribution', 'jquery'], function (Core, Parent, jQuery) {
    'use strict';
    var PageStore;

    return Parent.extend({

        /**
         * Event trigged on double click in node tree
         * @param {Object} event
         */
        onDoubleClick: function () {
            return;
        },

        setPageStore: function (pageStore) {
            PageStore = pageStore;
        },

        renderIn: function (selector) {
            var self = this;

            this.view.getTree().done(function (tree) {
                tree.render(selector);

                if (self.autoLoadRoot) {
                    self.loadTreeRoot(tree);
                }

                self.view.on('rootIsLoaded', function () {
                    var rootNode = tree.invoke('getNodeById', Core.get('root.uid'));

                    jQuery(rootNode.element).children('div.jqtree-element').find('span').addClass('txt-highlight');
                });

                self.treeView.on('click', function (event) {
                    if (event.node.is_fake === true) {
                        return;
                    }

                    var element = jQuery(event.node.element),
                        children = element.children('.jqtree-element'),
                        westBlock = element.parents('.ui-layout-west');

                    westBlock.find('.txt-highlight').removeClass('txt-highlight');
                    children.find('span').addClass('txt-highlight');

                    PageStore.applyFilter('byStatus', [0, 1, 2, 3]);
                    PageStore.applyFilter('byOffset', 1);
                    PageStore.applyFilter('byParent', event.node.uid);
                    PageStore.execute();
                });
            });

            return this;
        }
    });
});