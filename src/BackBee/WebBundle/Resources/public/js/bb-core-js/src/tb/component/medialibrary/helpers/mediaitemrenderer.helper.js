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
        'item.templates': 'src/tb/component/medialibrary/templates/'
    }
});
define(
    [
        'Core',
        'Core/Renderer',
        'jquery',
        'component!popin',
        'component!mask',
        'jsclass',
        'text!item.templates/media.viewmode.tpl',
        'text!item.templates/media.deletemode.tpl',
        'text!item.templates/media.editmode.tpl'
    ],
    function (Api, Renderer, jQuery, PopInManager, MaskManager) {
        'use strict';

        var trans = require('Core').get('trans') || function (value) { return value; },
            MediaItemRenderer = new JS.Class({
                initialize: function () {
                    this.mode = "editmode";
                    this.templates = {
                        'view': require('text!item.templates/media.viewmode.tpl'),
                        'edit': require('text!item.templates/media.editmode.tpl'),
                        'deleteContent': require('text!item.templates/media.deletemode.tpl')
                    };
                    this.mask = MaskManager.createMask({});
                    Renderer.addFilter("bytesToSize", this.bytetoSize);
                },

                bytetoSize: function (bytes) {
                    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
                        i;
                    if (bytes === 0) { return '0 Byte'; }
                    i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
                    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                },

                initPopin: function () {
                    if (this.popin) {
                        this.popin.destroy();
                    }
                    this.popin = PopInManager.createSubPopIn(this.selector.dialog, {
                        modal: true,
                        minHeight: 200,
                        minWidth: 450,
                        maxHeight: 500,
                        maxWidth: 450
                    });
                    return this.popin;
                },

                setSelector: function (selector) {
                    this.selector = selector;
                },

                getSelector: function () {
                    return this.selector;
                },

                setMode: function (mode) {
                    this.mode = mode;
                },

                hidePopin: function () {
                    if (this.popin) {
                        this.popin.destroy();
                    }
                },

                bindItemEvents: function (item, itemData) {
                    item = jQuery(item);
                    item.on('click',
                        '.show-media-btn', jQuery.proxy(this.handleMediaPreview, this, itemData));
                    item.on('click', '.del-media-btn', jQuery.proxy(this.deleteMedia, this, itemData));
                    item.on('click', '.edit-media-btn', jQuery.proxy(this.showMediaEditForm, this, itemData));
                    item.on('click', '.addandclose-btn', jQuery.proxy(this.addAndClose, this, itemData));
                    return item;
                },

                handleMediaPreview: function (media, e) {
                    this.selector.hideEditForm();
                    e.stopPropagation();
                    var self = this;
                    this.initPopin().setTitle(trans("media_preview"));
                    this.popin.setContent(jQuery("<p>" + trans("loading") + "...</p>"));
                    this.popin.display();
                    this.popin.moveToTop();
                    this.popin.mask();
                    this.loadMediaPreview(media.content).done(function (content) {
                        content = jQuery(content);
                        content.removeClass();
                        self.popin.setContent(content);
                        self.popin.unmask();
                    });
                },

                addAndClose: function (media, e) {
                    e.stopPropagation();
                    e.preventDefault();
                    this.getSelector().selectItems(media);
                    this.getSelector().close();
                    return false;
                },

                loadMediaPreview: function (content) {
                    var dfd = new jQuery.Deferred();
                    Api.ApplicationManager.invokeService("content.main.getRepository").done(function (contentRepository) {
                        contentRepository.getHtml(content.type, content.uid).done(dfd.resolve);
                    });
                    return dfd.promise();
                },


                showMediaEditForm: function (media, e) {
                    this.hidePopin();
                    e.stopPropagation();
                    this.selector.showMediaEditForm(media.type, media);
                },

                checkOrphanedContents: function (content) {
                    var self = this;
                    Api.ApplicationManager.invokeService("page.main.getPageRepository").done(function (pageRepository) {
                        pageRepository.findContents(content.type, content.uid).done(function (data) {
                            data = data || [];
                            var templateData = {
                                isOrphaned: (data.length === 0),
                                items: data
                            };
                            content = Renderer.render(self.templates.deleteContent, templateData);
                            self.popin.setContent(jQuery(content));
                            self.addButtons();
                            self.popin.display();
                            self.popin.moveToTop();
                        }).fail(function (response) {
                            self.popin.unmask();
                            Api.exception('MediaItemException', 57567, '[deleteMedia] MediaItemException error while deleting media ' + response);
                        });
                    });
                },

                deleteMedia: function (media, e) {
                    this.selector.hideEditForm();
                    e.stopPropagation();
                    this.media = media;
                    this.initPopin();
                    this.popin.setTitle(trans('delete_media'));
                    this.popin.setContent(jQuery("<p><strong>" + trans("loading") + "...</strong></p>"));
                    this.popin.display();
                    this.popin.moveToTop();
                    this.checkOrphanedContents(media.content);
                },

                render: function (mode, item) {
                    if (mode === 'list' || mode === 'grid') {
                        mode = this.mode;
                    }
                    var template = this.templates[mode],
                        data =  Renderer.render(template, item); //mode is unused
                    return this.bindItemEvents(data, item);
                },

                addButtons: function () {
                    var self = this;

                    this.popin.addButton(trans('yes'), function () {
                        self.popin.mask();
                        self.popin.setContent("<p>" + trans("please_wait_while_the_media_is_being_deleted") + "...</p>");
                        self.selector.deleteMedia(self.media);
                        self.popin.destroy();
                    });
                    this.popin.addButton(trans('no'), function () {
                        self.popin.destroy();
                    });
                }
            });
        return MediaItemRenderer;
    }
);