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
        'jquery',
        'text!tb.component/formbuilder/form/element/templates/nodeSelector_item.twig'
    ],
    function (Core, Renderer, jQuery, itemTemplate) {
        'use strict';

        var nodeSelector = Backbone.View.extend({

            mainSelector: Core.get('wrapper_toolbar_selector'),
            nodeSelectorClass: 'add_node',
            trashClass: 'trash',
            elementsWrapperClass: 'node_elements_wrapper',
            editClass: 'node-selector-edit',
            moveClass: 'move',
            moveDownClass: 'move-down',
            moveUpClass: 'move-up',
            listClass: 'nodeselector_list',

            /**
             * Initialize of node selector
             * @param {String} template
             * @param {String} formTag
             * @param {Object} element
             */
            initialize: function (template, formTag, element) {
                var maxEntry;

                this.el = formTag;
                this.template = template;
                this.element = element;
                this.elementSelector = 'form#' + this.el + ' .element_' + this.element.getKey();
                this.bindEvents();
                this.currentEditItem = null;

                maxEntry = parseInt(this.element.config.max_entry, 10);
                if (!isNaN(maxEntry)) {
                    if (maxEntry <= 0) {
                        maxEntry = null;
                    }
                }

                this.maxEntry = maxEntry;
            },

            bindEvents: function () {
                var self = this;

                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.nodeSelectorClass, jQuery.proxy(this.onClick, this));
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.trashClass, jQuery.proxy(this.onTrash, this));
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.editClass, jQuery.proxy(this.onEditClick, this));
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.moveClass, jQuery.proxy(this.onMove, this));

                //HAndle click page tree

                Core.Mediator.subscribe('before:form:submit', function (form) {
                    if (form.attr('id') === self.el) {
                        var nodes = self.getCurrentNodes(),
                            oldNodes = self.element.value,
                            element = jQuery(form).find('input[name="' + self.element.getKey() + '"]'),
                            i,
                            updated = false;

                        if (nodes.length !== oldNodes.length) {
                            updated = true;
                        } else {
                            for (i = 0; i < oldNodes.length; i = i + 1) {
                                if (oldNodes[i].pageUid !== nodes[i].pageUid) {
                                    updated = true;
                                    break;
                                }
                            }
                        }

                        if (updated === true) {
                            element.val('updated');
                        } else {
                            element.val('');
                        }
                    }
                });

                Core.Mediator.subscribe('on:form:render', function () {
                    self.updateAddButton();
                });
            },

            onEditClick: function (event) {
                this.currentEditItem = jQuery(event.currentTarget).parents('li:first');
                this.showTree();
            },

            onMove: function (event) {
                var target = jQuery(event.currentTarget),
                    li = target.parents('li:first'),
                    prev = li.prev('li'),
                    next = li.next('li');

                if (target.hasClass(this.moveUpClass)) {
                    if (prev.length > 0) {
                        prev.before(li);
                    }
                } else if (target.hasClass(this.moveDownClass)) {
                    if (next.length > 0) {
                        next.after(li);
                    }
                }

                this.updateMoveBtn();
            },

            updateMoveBtn: function () {
                var self = this,
                    list = jQuery(this.elementSelector + ' .' + this.listClass),
                    children = list.children('li'),
                    count = children.length;

                list.children('li').each(function (index) {

                    var element = jQuery(this),
                        moveDownBtn = element.find('.' + self.moveDownClass),
                        moveUpBtn = element.find('.' + self.moveUpClass);

                    moveDownBtn.addClass('hidden');
                    moveUpBtn.addClass('hidden');

                    if (count > 1) {
                        if (index === 0) {
                            moveDownBtn.removeClass('hidden');
                        } else if (index === count - 1) {
                            moveUpBtn.removeClass('hidden');
                        } else {
                            moveDownBtn.removeClass('hidden');
                            moveUpBtn.removeClass('hidden');
                        }
                    }
                });
            },

            updateAddButton: function () {
                var currentNodes = this.getCurrentNodes(),
                    btn = jQuery(this.elementSelector + ' .' + this.nodeSelectorClass);

                if (null !== this.maxEntry && this.maxEntry <= currentNodes.length) {
                    btn.addClass('hidden');
                } else {
                    btn.removeClass('hidden');
                }
            },

            onTrash: function (event) {
                jQuery(event.currentTarget).parent().remove();
                this.updateAddButton();
            },

            onClick: function () {
                this.currentEditItem = null;
                this.showTree();
            },

            showTree: function () {
                var self = this,
                    config = {
                        do_loading: true,
                        do_pagination: true,
                        site_uid: Core.get('site.uid'),
                        popin: true,
                        enable_siteSelection: true,
                        autoloadRoot: true,
                        popinId: 'popin_' + self.el
                    };

                if (self.pageTreeView === undefined) {
                    Core.ApplicationManager.invokeService('page.main.getPageTreeViewInstance').done(function (PageTreeView) {

                        self.pageTreeView = new PageTreeView(config);

                        self.pageTreeView.getTree().done(function (tree) {
                            self.pageTree = tree;
                            self.pageTreeView.loadTreeRoot();
                            self.bindTreeEvents();

                        });
                    });
                } else {
                    self.pageTree.display();
                }

                return false;
            },

            /**
             * Bind tree events
             */
            bindTreeEvents: function () {
                this.pageTreeView.treeView.on('tree.dblclick', jQuery.proxy(this.handleNodeSelection, this));
            },

            handleNodeSelection: function (event) {

                if (event.node.is_fake === true) {
                    return;
                }

                var selectedSite = this.pageTreeView.getSelectedSite(),
                    fieldTitle,
                    siteLabel = "",
                    elementsWrapper = jQuery(this.elementSelector).find(' .' + this.elementsWrapperClass + ' ul'),
                    data = {'pageUid': event.node.id, 'title': event.node.name},
                    item;

                if (selectedSite && this.pageTreeView.getAvailableSites() > 0) {
                    data.siteLabel = selectedSite.label;
                    siteLabel = "(" + data.siteLabel + ")";
                }

                if (this.currentEditItem !== null) {

                    this.currentEditItem.find('input.pageuid').val(data.pageUid);
                    fieldTitle = data.title + siteLabel;
                    this.currentEditItem.find('input.title').val(fieldTitle);

                    this.currentEditItem = null;

                    this.pageTree.hide();

                    return;
                }

                item = Renderer.render(itemTemplate, {'data': data});

                elementsWrapper.append(item);

                this.pageTree.hide();

                this.updateAddButton();

                this.updateMoveBtn();

                return false;
            },

            getCurrentNodes: function () {
                var elementsWrapper = jQuery(this.elementSelector).find(' .' + this.elementsWrapperClass + ' ul'),
                    nodes = [];

                elementsWrapper.children('li').each(function () {
                    var li = jQuery(this),
                        node = {
                            'title': li.find('input.title').val(),
                            'pageUid': li.find('input.pageuid').val()
                        };

                    nodes.push(node);
                });

                return nodes;
            },

            /**
             * Bind events and render template
             * @returns {String}
             */
            render: function () {
                var key,
                    items = [],
                    nodes = this.element.value;

                for (key in nodes) {
                    if (nodes.hasOwnProperty(key)) {
                        items.push(Renderer.render(itemTemplate, {'data': nodes[key]}));
                    }
                }

                return Renderer.render(this.template, {'element': this.element, 'items': items});
            }
        });

        return nodeSelector;
    }
);