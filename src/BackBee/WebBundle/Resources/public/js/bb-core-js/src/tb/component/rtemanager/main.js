define(['Core', 'jquery', 'jsclass'], function (Core, jquery) {
    'use strict';
    var adapter = {},
        AbstactRteAdapter = new JS.Class({

            initialize: function (config) {
                this.config = config;
                this.editor = null;
                this.isEnabled = false;
            },

            onInit: function () {
                Core.exception('PluginException', '85000', "AbstactRteAdapterException:onInit - Not implemented yet");
            },

            applyToContent: function () {
                Core.exception('PluginException', '85001', "AbstactRteAdapterException:applyToContent - Not implemented yet!");
            },

            triggerOnReady: function () {
                var params = ['rte:rteAdapter:isReady'],
                    args = Array.prototype.slice.call(arguments);
                jquery.merge(params, args);
                Core.Mediator.publish.apply(Core.Mediator, params);
            },

            triggerOnEdit: function (data) {
                Core.Mediator.publish('rte:rteAdapter:editedContent', data);
            },

            getEditor: function () {
                return this.editor;
            },

            enable: function () {
                this.isEnabled = true;
            },

            disable: function () {
                this.isEnabled = false;
            }
        });

    return {
        createAdapter: function (adapterName, def) {
            def.getName = (function () {
                return function () {
                    return adapterName;
                };
            }(adapterName));
            adapter = new JS.Class(AbstactRteAdapter, def);
            return adapter;
        },
        use: function (adapter) {
            require(['rteadapter!' + adapter]);
        }
    };
});