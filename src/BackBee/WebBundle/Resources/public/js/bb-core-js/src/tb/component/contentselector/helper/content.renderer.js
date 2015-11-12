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
require.config({
    paths: {
        'content.datastore': 'src/tb/component/contentselector/datastore/content.datastore'
    }
});
define(
    [
        'require',
        'content.datastore',
        'jsclass',
        'Core/Renderer',
        'jquery',
        'Core',
        'text!cs-templates/content.list.edit.view.tpl',
        'text!cs-templates/content.delete.tpl',
        'text!cs-templates/content.list.view.tpl'
    ],
    function (require) {
        'use strict';
        var Renderer = require('Core/Renderer'),
            jQuery = require('jquery'),
            trans = require('Core').get('trans'),
            Core = require('Core'),
            ContentRenderer = new JS.Class({
                initialize: function (selector) {

                    this.templates = {
                        viewmodelist: require('text!cs-templates/content.list.view.tpl'),
                        viewmodegrid: require('text!cs-templates/content.list.view.tpl'),
                        editmodelist: require('text!cs-templates/content.list.edit.view.tpl'),
                        editmodegrid: require('text!cs-templates/content.list.edit.view.tpl'),
                        deleteContent: require('text!cs-templates/content.delete.tpl')
                    };
                    this.itemData = null;
                    this.selector = selector;
                    this.restContentDataStore = require('content.datastore');
                    this.popinManager = require('component!popin');

                    this.mode = "viewmode";
                },

                getPopin: function (title) {
                    title = title || trans("content_preview");
                    if (this.popin) {
                        this.popin.destroy();
                    }
                    this.popin = this.popinManager.createSubPopIn(this.selector.popIn, {
                        modal: true,
                        minHeight: 200,
                        minWidth: 450,
                        maxHeight: 500,
                        maxWidth: 450,
                        title: title
                    });
                    return this.popin;
                },

                setEditMode: function () {
                    this.mode = "editmode";
                },

                setViewMode: function () {
                    this.mode = "viewmode";
                },

                setSelector: function (selector) {
                    this.selector = selector;
                },

                getSelector: function () {
                    return this.selector;
                },

                bindContentEvents: function (item, itemData) {
                    item = jQuery(item);
                    item.on('click', ".show-content-btn", jQuery.proxy(this.showContentPreview, this, itemData));
                    item.on('click', ".del-content-btn", jQuery.proxy(this.deleteContent, this, itemData));
                    item.on('click', ".addandclose-btn", jQuery.proxy(this.addAndCloseContent, this, itemData));
                    return item;
                },

                /* use cache, load item template according to render mode*/
                render: function (renderMode, item) {
                    var itemData = item;
                    renderMode = this.mode + renderMode;
                    item = Renderer.render(this.templates[renderMode], item);
                    return this.bindContentEvents(item, itemData);
                },

                addAndCloseContent: function (itemData, e) {
                    e.preventDefault();
                    this.getSelector().selectItems(itemData);
                    this.getSelector().close();
                    return false;
                },

                clearContent: function (content) {
                    var bbContents = jQuery(content).removeClass("bb-content").find(".bb-content");
                    bbContents.map(function (i) {
                        jQuery(bbContents[i]).removeClass("bb-content");
                    });
                    return jQuery(content);
                },

                showContentPreview: function (itemData, e) {
                    e.stopPropagation();
                    var self = this;
                    self.getPopin().setContent("<b>" + trans("loading") + "...</b>");
                    self.popin.mask();
                    self.popin.display();

                    Core.ApplicationManager.invokeService("content.main.getRepository").done(function (contentRepository) {
                        contentRepository.getHtml(itemData.type, itemData.uid).done(function (response) {
                            response = self.clearContent(jQuery(response));
                            self.popin.setContent(response);
                            jQuery('#' + self.popin.getId()).dialog("option", "maxHeight", 450);
                        }).fail(function (response) {
                            Core.exception('ContentRendererException', 57567, 'error while showing showContentPreview ' + response);
                        }).always(self.popin.unmask);
                    });
                },

                deleteContent: function (itemData, e) {
                    e.stopPropagation();
                    var self = this,
                        content;
                    self.itemData = itemData;
                    self.getPopin().setContent("<b>" + trans("loading") + "...</b>");
                    self.popin.setTitle(trans("delete_content"));
                    self.popin.mask();
                    self.popin.display();
                    Core.ApplicationManager.invokeService("page.main.getPageRepository").done(function (pageRepository) {
                        pageRepository.findContents(itemData.type, itemData.uid).done(function (data) {

                            data = jQuery.isArray(data) ? data : [data];
                            self.addButtons();
                            var templateData = {
                                isOrphaned: (data.length === 0),
                                items: data
                            };
                            content = Renderer.render(self.templates.deleteContent, templateData);
                            self.popin.unmask();
                            self.popin.setContent(jQuery(content));
                        }).fail(function (response) {
                            self.popin.unmask();
                            Core.exception('ContentRendererException', 57567, '[deleteContent] ContentRendererException error while deleting content ' + response);
                        });
                    });
                },

                addButtons: function () {
                    var self = this,
                        contentData = {};
                    this.deleting = true;
                    self.popin.addButton(trans("yes"), function () {
                        self.popin.mask();
                        self.popin.setContent("<p>" + trans("please_wait_while_the_content_is_being_deleted") + "...</p>");
                        contentData.uid = self.itemData.uid;
                        contentData.type = self.itemData.type;
                        self.restContentDataStore.remove(contentData).always(function () {
                            self.popin.unmask();
                            self.popin.hide();
                        });
                    });
                    self.popin.addButton(trans("no"), function () {
                        self.popin.hide();
                    });
                }
            });
        return ContentRenderer;
    }
);