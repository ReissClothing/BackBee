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
        'text!tb.component/formbuilder/form/element/templates/linkSelector_item.twig',
        'tb.component/linkselector/main'
    ],
    function (Core, Renderer, jQuery, itemTemplate) {
        'use strict';

        var LinkSelector = Backbone.View.extend({

            mainSelector: Core.get('wrapper_toolbar_selector'),
            linkSelectorClass: 'add_link',
            trashClass: 'trash',
            elementsWrapperClass: 'link_elements_wrapper',
            editClass: 'link-selector-edit',
            moveClass: 'move',
            moveDownClass: 'move-down',
            moveUpClass: 'move-up',
            listClass: 'linkselector_list',

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
                this.linkSelector = require('tb.component/linkselector/main').create();
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

                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.linkSelectorClass, jQuery.proxy(this.onClick, this));
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.trashClass, jQuery.proxy(this.onTrash, this));
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.editClass, jQuery.proxy(this.onEditClick, this));
                jQuery(this.mainSelector).on('click', this.elementSelector + ' .' + this.moveClass, jQuery.proxy(this.onMove, this));

                this.linkSelector.on('close', jQuery.proxy(this.handleLinkSelection, this));

                Core.Mediator.subscribe('before:form:submit', function (form) {
                    if (form.attr('id') === self.el) {
                        var links = self.getCurrentLinks(),
                            oldLinks = self.element.value,
                            element = jQuery(form).find('input[name="' + self.element.getKey() + '"]'),
                            i,
                            updated = false;

                        if (links.length !== oldLinks.length) {
                            updated = true;
                        } else {
                            for (i = 0; i < oldLinks.length; i = i + 1) {
                                if (oldLinks[i].url !== links[i].url ||
                                        oldLinks[i].title !== links[i].title ||
                                        oldLinks[i].target !== links[i].target ||
                                        oldLinks[i].pageUid !== links[i].pageUid) {

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
                this.linkSelector.show();
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

            onTrash: function (event) {
                jQuery(event.currentTarget).parent().remove();
                this.updateAddButton();
            },

            onClick: function () {
                this.currentEditItem = null;
                this.linkSelector.show();
            },

            updateAddButton: function () {
                var currentLinks = this.getCurrentLinks(),
                    btn = jQuery(this.elementSelector + ' .' + this.linkSelectorClass);

                if (null !== this.maxEntry && this.maxEntry <= currentLinks.length) {
                    btn.addClass('hidden');
                } else {
                    btn.removeClass('hidden');
                }
            },

            handleLinkSelection: function (data) {

                if (this.currentEditItem !== null) {

                    this.currentEditItem.find('input.pageuid').val(data.pageUid);
                    this.currentEditItem.find('input.link').val(data.url);

                    this.currentEditItem = null;

                    return;
                }

                var elementsWrapper = jQuery(this.elementSelector).find(' .' + this.elementsWrapperClass + ' ul'),
                    item = Renderer.render(itemTemplate, {'data': data});

                elementsWrapper.append(item);

                this.updateAddButton();

                this.updateMoveBtn();
            },

            getCurrentLinks: function () {
                var elementsWrapper = jQuery(this.elementSelector).find(' .' + this.elementsWrapperClass + ' ul'),
                    links = [];

                elementsWrapper.children('li').each(function () {
                    var li = jQuery(this),
                        link = {
                            'url': li.find('input.link').val(),
                            'title': li.children('input.title').val(),
                            'pageUid': li.children('input.pageuid').val(),
                            'target': li.find('select.target option:selected').val()
                        };

                    links.push(link);
                });

                return links;
            },

            /**
             * Bind events and render template
             * @returns {String}
             */
            render: function () {
                var key,
                    items = [],
                    links = this.element.value;

                for (key in links) {
                    if (links.hasOwnProperty(key)) {
                        items.push(Renderer.render(itemTemplate, {'data': links[key]}));
                    }
                }

                return Renderer.render(this.template, {'element': this.element, 'items': items});
            }
        });

        return LinkSelector;
    }
);