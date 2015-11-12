require.config({
    paths: {
        'rteadapter': 'src/tb/apps/content/plugins/rte/adapter/rteadapter'
    }
});
define(['content.pluginmanager', 'component!rtemanager', 'Core', 'jquery', 'jsclass'], function (PluginManager, RteManager, Core, jQuery) {
    'use strict';
    PluginManager.registerPlugin("rte", {
        scope: PluginManager.scope.CONTENT,
        onInit: function () {
            this.readyDfd = new jQuery.Deferred();
            this.adapterIsLoaded = false;
        },

        loadRte: function () {
            var self = this,
                adapter = this.getConfig("adapter");
            RteManager.use(adapter);
            Core.Mediator.subscribe("rte:rteAdapter:isReady", function (adaptor) {
                self.adapterIsLoaded = true;
                self.rteAdapter = adaptor;
                self.readyDfd.resolve();
            });
        },

        onDisable: function () {
            this.callSuper();
            if (this.rteAdapter) {
                this.rteAdapter.disable();
            }
        },

        onEnable: function () {
            var self = this;
            this.callSuper();
            this.loadRte();
            if (!this.rteAdapter && this.readyDfd) {
                this.readyDfd.done(function () {
                    self.rteAdapter.applyToContent(self.getCurrentContent());
                });
            } else {
                self.rteAdapter.applyToContent(self.getCurrentContent());
            }
        },

        canApplyOnContext: function () {
            return this.context.scope === "contribution.content";
        }
    });
});