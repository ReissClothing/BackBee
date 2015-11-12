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
/*global CKEDITOR:false */
define(
    [
        'Core',
        'Core/Utils',
        'jquery',
        'component!rtemanager'
    ],
    function (Core, Utils, jQuery, RteManager) {
        'use strict';

        return RteManager.createAdapter('cke', {

            onInit: function () {
                this.editors = [];
                this.editableConfig = {};
                this.conciseInfos = {};
                this.identifierMap = {};
                this.lastInstance = null;
                this.editorContainer = '#content-contrib-tab .bb-cke-wrapper';
                var lib = [],
                    self = this;
                if (this.config.hasOwnProperty('libName')) {
                    lib.push(this.config.libName);
                }
                if (this.config.hasOwnProperty('editableConfig')) {
                    this.editableConfig = this.config.editableConfig;
                }

                this.handleContentEvents();
                Utils.requireWithPromise(lib).done(function () {
                    self.editor = CKEDITOR;
                    self.editor.disableAutoInline = true;
                    self.editor.dtd.$editable.span = 1;
                    self.editor.dtd.$editable.a = 1;
                    /* extends CKEditor config with user config here*/
                    jQuery.extend(self.editor.config, self.config);
                    CKEDITOR.on('instanceReady', jQuery.proxy(self.handleInstance, self));
                    CKEDITOR.on('currentInstance', function () {
                        self.stickEditor({
                            editor: CKEDITOR.currentInstance
                        });
                        self.lastInstance = CKEDITOR.currentInstance;
                    });

                    self.triggerOnReady(self);
                });
            },

            handleContentEvents : function () {
                jQuery(document).on('click', jQuery.proxy(this.blurEditor, this));
            },

            blurEditor: function () {
                if (this.lastInstance) {
                    this.lastInstance.fire("blur", {editor: this.lastInstance});
                }
            },

            stickEditor: function (e) {
                if (!e.editor) {
                    return;
                }
                var editorHtml = jQuery("#cke_" + e.editor.name);
                if (jQuery(this.editorContainer).find(editorHtml).length) {
                    return;
                }

                jQuery(this.editorContainer).append(editorHtml);
            },

            getEditableContents: function (content) {
                var dfd = new jQuery.Deferred(),
                    self = this;
                if (!this.conciseInfos.hasOwnProperty(content.uid)) {
                    Core.ApplicationManager.invokeService('content.main.getEditableContent', content).done(function (promise) {
                        promise.done(function (editableContents) {
                            self.conciseInfos[content.uid] = editableContents;
                            dfd.resolve(editableContents);
                        });
                    });
                } else {
                    dfd.resolve(self.conciseInfos[content.uid]);
                }
                return dfd.promise();
            },

            applyToContent: function (content) {
                var self = this,
                    editable,
                    nodeSelector;

                this.getEditableContents(content).done(function (editableContents) {
                    if (!editableContents.length) {
                        return;
                    }
                    jQuery.each(editableContents, function (i) {
                        editable = editableContents[i];
                        if (!self.identifierMap[editable.uid]) {
                            self.identifierMap[editable.uid] = editable.jQueryObject.selector;
                        }
                        nodeSelector = self.identifierMap[editable.uid];
                        if (!jQuery.contains(document, editable.jQueryObject.get(0))) {
                            editable.jQueryObject = content.jQueryObject.find(nodeSelector).eq(0);
                        }
                        self.applyToElement(editable.jQueryObject);
                    });
                });
            },

            handleInstance: function (event) {
                var editor = event.editor;
                this.editors.push(editor);
                editor.on("blur", jQuery.proxy(this.handleContentEdition, this));
            },

            handleContentEdition: function (evt) {

                if (evt.editor.checkDirty()) {
                    this.triggerOnEdit({
                        node: evt.editor.container.$,
                        data: evt.editor.getData()
                    });
                    /* save value here */
                    Core.ApplicationManager.invokeService('content.main.getContentManager').done(function (ContentManager) {
                        var content = ContentManager.getContentByNode(jQuery(evt.editor.container.$));
                        content.set('value', evt.editor.getData());
                    });
                }
            },

            applyToElement: function (element) {
                element = jQuery(element);
                if (!element.length) {
                    return;
                }
                if (element.hasClass('cke_editable_inline')) {
                    return true;
                }
                element.attr('contenteditable', true);
                var conf = element.data('rteConfig') || 'basic',
                    rteConfig = this.editableConfig[conf];
                this.editor.inline(jQuery(element).get(0), rteConfig);
            },

            enable: function () { this.callSuper(); },

            disable: function () {
                var self = this,
                    editable;
                jQuery.each(this.editors, function (i) {
                    editable = self.editors[i];
                    jQuery(editable.container.$).removeClass('cke_editable cke_editable_inline');
                    jQuery(editable.container.$).removeAttr('contenteditable');
                    editable.destroy();
                });
                this.editors = [];
            },

            getEditor: function () {
                return this.editor;
            }
        });
    }
);