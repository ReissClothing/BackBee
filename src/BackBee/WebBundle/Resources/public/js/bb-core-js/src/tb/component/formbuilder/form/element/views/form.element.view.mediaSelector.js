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
        'text!tb.component/formbuilder/form/element/templates/mediaSelector_item.twig',
        'component!medialibrary',
        'jquery'
    ],
    function (Core, Renderer, Backbone, itemTemplate, MediaLibrary, jQuery) {

        'use strict';

        var MediaSelectorView = Backbone.View.extend({

            mainSelector: Core.get('wrapper_toolbar_selector'),

            initialize: function (template, formTag, element) {
                var maxEntry;

                this.el = formTag;
                this.template = template;
                this.element = element;

                this.trashClass = 'trash';
                this.trashAllClass = 'trashall';
                this.addMediaClass = 'addmedia';
                this.listClass = 'media_list';
                this.moveClass = 'move';
                this.moveDownClass = 'move-down';
                this.moveUpClass = 'move-up';
                this.elementSelector = 'form#' + this.el + ' .element_' + this.element.getKey();

                maxEntry = parseInt(this.element.config.max_entry, 10);
                if (!isNaN(maxEntry)) {
                    if (maxEntry <= 0) {
                        maxEntry = null;
                    }
                }

                this.maxEntry = maxEntry;

                this.bindEvents();
            },

            bindEvents: function () {
                var self = this,
                    mainNode = jQuery(this.mainSelector);

                mainNode.on('click', this.elementSelector + ' .' + this.trashClass, jQuery.proxy(this.onDelete, this));
                mainNode.on('click', this.elementSelector + ' .' + this.moveClass, jQuery.proxy(this.onMove, this));

                mainNode.on('click', this.elementSelector + ' .' + this.trashAllClass, jQuery.proxy(this.onDeleteAll, this));
                mainNode.on('click', this.elementSelector + ' .' + this.addMediaClass, jQuery.proxy(this.onAddMedia, this));

                Core.Mediator.subscribe('before:form:submit', function (form) {
                    if (form.attr('id') === self.el) {
                        var contents = self.getCurrentContents(),
                            oldContents = self.element.value,
                            element = jQuery(form).find('input[name="' + self.element.getKey() + '"]'),
                            i,
                            updated = false;

                        if (contents.length !== oldContents.length) {
                            updated = true;
                        } else {
                            for (i = 0; i < oldContents.length; i = i + 1) {
                                if (oldContents[i].uid !== contents[i].uid) {
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

            getCurrentContents: function () {
                var list = jQuery(this.elementSelector + ' .' + this.listClass),
                    contents = [];

                list.children('li').each(function () {
                    var li = jQuery(this);

                    contents.push({'uid': li.data('uid'), 'type': li.data('type')});
                });

                return contents;
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
                var currentMedias = this.getCurrentContents(),
                    btn = jQuery(this.elementSelector + ' .' + this.addMediaClass);

                if (null !== this.maxEntry && this.maxEntry <= currentMedias.length) {
                    btn.addClass('hidden');
                } else {
                    btn.removeClass('hidden');
                }
            },

            onDeleteAll: function () {
                jQuery(this.elementSelector + ' .' + this.listClass).children('li').remove();
                this.updateAddButton();
            },

            onDelete: function (event) {
                var target = jQuery(event.currentTarget),
                    li = target.parents('li:first');

                li.remove();

                this.updateAddButton();

                this.updateMoveBtn();
            },

            onMove: function (event) {
                var target = jQuery(event.currentTarget),
                    li = target.parents('li:first'),
                    prev = li.prev('li'),
                    next = li.next('li');

                if (target.hasClass('move-up')) {
                    if (prev.length > 0) {
                        prev.before(li);
                    }
                } else {
                    if (next.length > 0) {
                        next.after(li);
                    }
                }

                this.updateMoveBtn();
            },

            onAddMedia: function () {
                if (this.mediaLibraryIsLoaded !== true) {
                    this.mediaLibrary = MediaLibrary.createMediaLibrary();
                    this.mediaLibrary.on('close', jQuery.proxy(this.handleMediaSelection, this));

                    this.mediaLibrary.display();

                    this.mediaLibraryIsLoaded = true;
                } else {
                    this.mediaLibrary.display();
                }
            },

            handleMediaSelection: function (medias) {
                var key;

                if (medias.length > 0) {
                    for (key in medias) {
                        if (medias.hasOwnProperty(key)) {
                            this.addItem(medias[key]);
                        }
                    }

                    this.updateMoveBtn();
                }
            },

            addItem: function (media) {
                var list = jQuery(this.elementSelector + ' .' + this.listClass);

                media.uid = media.content.uid;
                media.type = media.content.type;
                media.folder_uid = media.mediaFolder;
                media.media_id = media.id;

                list.prepend(Renderer.render(itemTemplate, {'element': this.element, 'item': media}));

                this.updateAddButton();
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {String} html
             */
            render: function () {
                var key,
                    items = [],
                    medias = this.element.value;

                for (key in medias) {
                    if (medias.hasOwnProperty(key)) {
                        items.push(Renderer.render(itemTemplate, {'element': this.element, 'item': medias[key]}));
                    }
                }

                return Renderer.render(this.template, {element: this.element, 'items': items});
            }
        });

        return MediaSelectorView;
    }
);