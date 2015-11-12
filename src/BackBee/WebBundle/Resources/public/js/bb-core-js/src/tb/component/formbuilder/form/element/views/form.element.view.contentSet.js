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
        'require',
        'Core/Renderer',
        'BackBone',
        'jquery',
        'text!tb.component/formbuilder/form/element/templates/contentSet_item.twig',
        'component!contentselector'
    ],
    function (Core, require, Renderer, Backbone, jQuery, itemTemplate) {

        'use strict';

        var ContentSetView = Backbone.View.extend({

            blockClass: '.bb-block',
            mainSelector: Core.get('wrapper_toolbar_selector'),

            initialize: function (template, formTag, element) {

                this.el = formTag;
                this.template = template;
                this.element = element;

                this.editClass = 'edit';
                this.trashClass = 'trash';
                this.trashAllClass = 'trashall';
                this.addContentClass = 'addcontent';
                this.searchContentClass = 'searchcontent';
                this.listClass = 'contentset_list';
                this.moveClass = 'move';
                this.moveDownClass = 'move-down';
                this.moveUpClass = 'move-up';
                this.elementSelector = 'form#' + this.el + ' .element_' + this.element.getKey();

                this.bindEvents();
            },

            bindEvents: function () {
                var self = this,
                    mainNode = jQuery(this.mainSelector);

                mainNode.on('click', this.elementSelector + ' .' + this.editClass, jQuery.proxy(this.onEdit, this));
                mainNode.on('click', this.elementSelector + ' .' + this.trashClass, jQuery.proxy(this.onDelete, this));
                mainNode.on('click', this.elementSelector + ' .' + this.moveClass, jQuery.proxy(this.onMove, this));

                mainNode.on('click', this.elementSelector + ' .' + this.trashAllClass, jQuery.proxy(this.onDeleteAll, this));
                mainNode.on('click', this.elementSelector + ' .' + this.addContentClass, jQuery.proxy(this.onAddContent, this));
                mainNode.on('click', this.elementSelector + ' .' + this.searchContentClass, jQuery.proxy(this.onSearchContent, this));

                Core.Mediator.subscribe('before:form:submit', function (form) {
                    if (form.attr('id') === self.el) {
                        var contents = self.getCurrentContents(),
                            oldContents = self.element.children,
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

            onEdit: function (event) {
                var target = jQuery(event.currentTarget),
                    li = target.parents('li');

                Core.ApplicationManager.invokeService('content.main.getContentManager').done(function (ContentManager) {
                    var content = ContentManager.buildElement({'uid': li.attr('data-uid'), 'type': li.attr('data-type')});

                    Core.ApplicationManager.invokeService('content.main.getEditionWidget').done(function (Edition) {
                        Edition.show(content);
                    });
                });
            },

            onSearchContent: function () {
                var self = this,
                    type = this.element.object_type;

                if (this.contentSelector === undefined) {
                    this.contentSelector = require('component!contentselector').createContentSelector({
                        mode: 'edit',
                        resetOnClose: true
                    });
                    this.contentSelector.on('close', jQuery.proxy(this.handleContentSelection, this));
                }

                Core.ApplicationManager.invokeService('content.main.getDefinitionManager').done(function (DefinitionManager) {
                    var definition = DefinitionManager.find(type),
                        accepts = definition.accept;

                    self.contentSelector.setContenttypes(accepts);
                    self.contentSelector.display();
                });
            },

            handleContentSelection: function (selections) {
                var self = this,
                    contentInfos,
                    content;

                if (selections.length > 0) {
                    Core.ApplicationManager.invokeService('content.main.getContentManager').done(function (ContentManager) {
                        var key;

                        for (key in selections) {
                            if (selections.hasOwnProperty(key)) {
                                contentInfos = selections[key];
                                content = ContentManager.buildElement(contentInfos);
                                self.addItem(content);
                            }
                        }
                    });
                }
            },

            onAddContent: function () {
                var self = this,
                    type = this.element.object_type;

                Core.ApplicationManager.invokeService('content.main.getDefinitionManager').done(function (DefinitionManager) {
                    var definition = DefinitionManager.find(type),
                        accepts = definition.accept;

                    Core.ApplicationManager.invokeService('content.main.getDialogContentsListWidget').done(function (DialogContentsList) {
                        self.DialogContentsList = DialogContentsList;

                        if (accepts.length === 1) {
                            self.createElement(accepts[0]);
                        } else if (accepts.length === 0) {
                            self.showAddContentPopin();
                        } else {
                            self.showAddContentPopin(self.buildContents(accepts));
                        }
                    });
                });
            },

            /**
             * Bind events of add content popin
             */
            bindAddPopinEvents: function () {
                jQuery('#' + this.widget.popin.getId()).off('click.contentsetadd').on('click.contentsetadd', this.blockClass, jQuery.proxy(this.onContentAddPopinClick, this));
            },

            onContentAddPopinClick: function (event) {

                this.widget.hide();

                var currentTarget = jQuery(event.currentTarget),
                    img = currentTarget.find('img'),
                    type = img.data('bb-type');

                this.createElement(type);

                return false;
            },

            /**
             * Build contents with definition and type
             * @param {Object} accepts
             * @returns {Array}
             */
            buildContents: function (accepts) {
                var contents = [];

                Core.ApplicationManager.invokeService('content.main.getDefinitionManager').done(function (DefinitionManager) {
                    var key;

                    for (key in accepts) {
                        if (accepts.hasOwnProperty(key)) {
                            contents.push(DefinitionManager.find(accepts[key]));
                        }
                    }
                });

                return contents;
            },

            /**
             * Show popin and bind events
             * @param {Mixed} contents
             */
            showAddContentPopin: function (contents) {
                var config = {};

                if (this.widget === undefined) {
                    if (contents !== undefined) {
                        config.contents = contents;
                    }
                    this.widget = new this.DialogContentsList(config);
                }
                this.widget.show();
                this.bindAddPopinEvents();
            },

            createElement: function (type) {
                var self = this;

                Core.ApplicationManager.invokeService('content.main.getContentManager').done(function (ContentManager) {
                    ContentManager.createElement(type).done(function (content) {
                        self.addItem(content);
                    });
                });
            },

            addItem: function (content) {
                var list = jQuery(this.elementSelector + ' .' + this.listClass);

                list.prepend(Renderer.render(itemTemplate, {'element': this.element, 'item': content}));

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

                    if (index === 0) {
                        moveDownBtn.removeClass('hidden');
                    } else if (index === count - 1) {
                        moveUpBtn.removeClass('hidden');
                    } else {
                        moveDownBtn.removeClass('hidden');
                        moveUpBtn.removeClass('hidden');
                    }
                });
            },

            onDeleteAll: function () {
                jQuery(this.elementSelector + ' .' + this.listClass).children('li').remove();
            },

            onDelete: function (event) {
                var target = jQuery(event.currentTarget),
                    li = target.parents('li:first');

                li.remove();

                this.updateMoveBtn();
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {String} html
             */
            render: function () {
                var key,
                    items = [],
                    children = this.element.children,
                    child;

                for (key in children) {
                    if (children.hasOwnProperty(key)) {
                        child = children[key];
                        items.push(Renderer.render(itemTemplate, {'element': this.element, 'item': child}));
                    }
                }

                return Renderer.render(this.template, {element: this.element, 'items': items});
            }
        });

        return ContentSetView;
    }
);