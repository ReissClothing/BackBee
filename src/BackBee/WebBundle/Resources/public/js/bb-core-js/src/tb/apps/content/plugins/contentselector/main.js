define(
    [
        'content.pluginmanager',
        'Core',
        'component!contentselector',
        'component!translator',
        'jquery',
        'content.manager',
        'jsclass'
    ],
    function (
        PluginManager,
        Core,
        ContentSelector,
        Translator,
        jQuery,
        ContentManager
    ) {
        'use strict';

        PluginManager.registerPlugin('contentselector', {
            onInit: function () {
                this.contentSelector = ContentSelector.createContentSelector({
                    mode: 'edit'
                });
                this.contentSelector.on('close', jQuery.proxy(this.handleContentSelection, this));
            },

            handleContentSelection: function (selections) {
                var contentInfos,
                    position,
                    content,
                    self = this;
                if (!selections.length) {
                    return;
                }
                jQuery.each(selections, function (i) {
                    try {
                        contentInfos = selections[i];
                        content = ContentManager.buildElement(contentInfos);
                        position = self.getConfig("appendPosition");
                        position = (position === "bottom") ? "last" : 0;
                        self.getCurrentContent().append(content, position);
                    } catch (e) {
                        Core.exception('ContentSelectorPluginException', 50000, e);
                    }
                });
            },

            showContentSelector: function () {
                /* set Accept and other things */
                var currentContent = this.getCurrentContent(),
                    accept = currentContent.definition.accept;

                this.contentSelector.setContenttypes(accept);
                this.contentSelector.display();
            },

            canApplyOnContext: function () {
                return this.getCurrentContent().isAContentSet();
            },

            getActions: function () {
                var self = this;
                return [{
                    ico: 'fa fa-th-large',
                    cmd: self.createCommand(self.showContentSelector, self),
                    label: Translator.translate('content_selector'),
                    checkContext: function () {
                        return self.canApplyOnContext();
                    }
                }];
            }
        });
    }
);
