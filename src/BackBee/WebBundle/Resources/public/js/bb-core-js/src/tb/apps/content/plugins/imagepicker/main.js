/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Image picker allows us to edit an by using the media library
 *
 */
define(['Core/Utils', 'content.pluginmanager', 'content.manager', 'jquery', 'component!translator'], function (Utils, PluginManager, ContentManager, jQuery, Translator) {
    'use strict';


    PluginManager.registerPlugin('imagepicker', {

        MEDIA_TYPE : "Media/Image",

        onInit: function () {
            this.commandsQueue = [];
            this.mediaSelectorIsReady = false;
            this.isProcessing = false;
            var showCommand = this.createCommand(jQuery.proxy(this.showMediaSelectorCmd, this));
            this.commandsQueue.push(showCommand);
        },

        canApplyOnContext: function () {
            return (this.getCurrentContentType() === this.MEDIA_TYPE);
        },

        displaySelector: function () {
            this.mediaSelectorIsReady = true;
            this.isProcessing = false;
            this.mediaLibrary.display();
        },

        eventHandler: function (selections) {
            if (!selections.length) { return; }
            var lastIndex =  selections.length - 1,
                selection = selections[lastIndex],
                content = ContentManager.buildElement(selection.content);
            this.replaceCurrentContentWith(content);
        },

        replaceCurrentContentWith: function (content) {
            ContentManager.replaceWith(this.getCurrentContent(), content);
        },

        attachEvents: function (mediaLibrary) {
            mediaLibrary.on("close", jQuery.proxy(this.eventHandler), this);
        },

        showMediaSelectorCmd: function () {
            var self = this;
            if (!this.mediaSelectorIsReady) {
                Utils.requireWithPromise(["component!medialibrary"]).done(function (mediaLibraryHelper) {
                    self.mediaLibrary = mediaLibraryHelper.createMediaLibrary({});
                    self.attachEvents(self.mediaLibrary);
                    self.displaySelector();
                });
            } else {
                self.displaySelector();
            }
        },

        processCommands: function () {
            var self = this;
            if (this.isProcessing) {
                return;
            }
            this.isProcessing = true;
            jQuery.each(this.commandsQueue, function (i) {
                self.commandsQueue[i].execute();
                return;
            });
        },

        getActions: function () {
            var self = this;
            return [{
                'ico': 'fa fa-th',
                cmd: self.createCommand(self.processCommands, self),
                label: Translator.translate('media_selector'),
                checkContext: function () {
                    return self.canApplyOnContext();
                }
            }];
        }


    });

});