require.config({
    paths: {
        'contentplugin': 'src/tb/apps/content/plugins/contentplugin',
        'actionContainer': 'src/tb/apps/content/components/ContentActionWidget'
    }
});
define(['Core', 'jquery', 'Core/Utils', 'Core/Api', 'actionContainer', 'underscore', 'jsclass'], function (Core, jQuery, Utils, Api, ContentActionWidget, underscore) {

    'use strict';

    var mediator = Core.Mediator,
        pluginsInfos = {},
        scopeInfos = {},
        instance = null,
        AbstractPlugin = new JS.Class({

            initialize: function () {
                this.context = {};
                this.enabled = false;
                this.state = {};
                this.config = {};
                this.accept = {};
            },

            onInit: function () {
                Api.exception('PluginException', 75001, 'onInit must be overrided');
            },

            setAccept: function () {
                return null;
            },

            /* allowed params as radical:blaze:strange */
            getConfig: function (key) {
                if (!key) {
                    return this.config;
                }

                var keyInfos = key.split(':'),
                    length = keyInfos.length,
                    cpt = 0,
                    tmpResult,
                    paramName,
                    result = null;

                if (!length) {
                    return result;
                }

                if (length === 1) {
                    result = this.config[key];
                    return result;
                }

                while (cpt <= length) {
                    paramName = keyInfos[cpt];
                    tmpResult = (!tmpResult) ? this.config[paramName] : tmpResult[paramName];
                    result = tmpResult;
                    cpt = cpt + 1;
                }

                return result;
            },

            setConfig: function (config) {
                this.config = config;
            },

            onDisable: function () {
                this.enabled = false;
            },

            onEnable: function () {
                this.enabled = true;
            },

            setContentState: function (key, value) {
                if (!key && typeof key !== 'string') {
                    Api.exception('PluginException', 75002, 'setContentState key');
                }

                if (value === 'undefined') {
                    Api.exception('PluginException', 75003, 'setContentState value can\'t be undefined');
                }

                if (!this.context.hasOwnProperty('content')) {
                    Api.exception('PluginException', 75004, 'setContentState a content must be provided');
                }

                var contentState = this.state[this.getCurrentContent()];

                if (!contentState) {
                    this.state[this.getCurrentContent()] = {};
                    contentState = this.state[this.getCurrentContent()];
                }

                contentState[key] = value;
            },

            getContentState: function (key) {
                var result = null,
                    stateConfig;

                if (!this.context.hasOwnProperty('content')) {
                    Api.exception('PluginException', 75005, 'getContentState a content must be provided');
                }

                stateConfig = this.state[this.getCurrentContent()];

                if (stateConfig && stateConfig.hasOwnProperty(key)) {
                    result = stateConfig[key];
                }

                return result;
            },

            isEnabled: function () {
                return this.enabled;
            },

            getCurrentContent: function () {
                var result = null;

                if (this.context.hasOwnProperty('content')) {
                    result = this.context.content;
                }

                return result;
            },

            getCurrentContentNode: function () {
                var result = '',
                    currentContent = this.getCurrentContent();

                if (currentContent) {
                    result = currentContent.jQueryObject;
                }

                return result;
            },

            getCurrentContentType: function () {
                return this.context.content.type;
            },

            canApplyOnContext: function () {
                return false;
            },

            createCommand: function (func, context) {
                if (typeof func !== 'function') {
                    Api.exception('PluginException', 75006, 'createCommand func must be a function.');
                }

                var Command, funcContext = context || this;

                Command = (function (f, c) {
                    return function () {
                        this.execute = function () {
                            f.call(c);
                        };
                    };
                }(func, funcContext));

                return new Command();
            },

            setContext: function (context) {
                if (!context) {
                    Api.exception('PluginException', 75007, 'setContext func must be a function.');
                }

                var previousContext = this.context;

                this.context = context;

                this.onContextChange(previousContext);
            },

            onContextChange: function () {
                return;
            },

            getActions: function () {
                return [];
            }
        }),
        PluginManager = new JS.Class({

            initialize: function () {
                this.pluginsInfos = pluginsInfos;
                this.enabled = false;
                this.currentContent = null;
                this.pluginsInstance = {};
                this.CLICK_CONTEXT = "on:classcontent:click";
                this.RIGHT_CLICK_CONTEXT = "on:classcontent:contextmenu";
                this.loadingErrorInfos = {};
                this.contentActionWidget = new ContentActionWidget();
                this.contentActionWidget.setDomTag(Core.get('wrapper_toolbar_selector'));
                this.contentPlugins = {};
                this.pluginActions = [];
                this.actionsPosition = {};
                this.currentScope = null; // block //
                this.pluginInfos = {}; //plugin : config --> plugin is unique
                this.bindEvents();
            },

            bindEvents: function () {
                mediator.subscribe('on:pluginManager:loadingErrors', jQuery.proxy(this.handleLoadingErrors, this), this);
                mediator.subscribe('on:pluginManager:loading', jQuery.proxy(this.handleLoading, this), this);
            },

            registerNameSpace: function () {
                Api.exception('PluginException', 75008, "Not implemented yet");
            },

            registerScope: function (scope) {
                this.currentScope = scope;
            },

            getContentPlugins: function (contentType) {
                var plugins = this.contentPlugins[contentType] || [],
                    pluginConfig = Core.config('plugins'),
                    namespaces = Core.config('plugins:namespace');

                if (plugins.length) {
                    return plugins;
                }

                jQuery.each(namespaces, function (namespace) {
                    var pluginInfos = pluginConfig[namespace];

                    jQuery.each(pluginInfos, function (pluginName) {
                        var pluginConf = pluginInfos[pluginName];

                        if (jQuery.inArray(contentType, pluginConf.accept) !== -1) {
                            plugins.push(namespace + ':' + pluginName);
                        }

                        /* apply for all */
                        if (jQuery.inArray('*', pluginConf.accept) !== -1) {
                            plugins.push(namespace + ':' + pluginName);
                        }
                    });
                });

                this.contentPlugins[contentType] = plugins;

                /*handle conf too*/
                return plugins;
            },

            disable: function () {
                this.enabled = false;
            },

            isEnabled: function () {
                return this.enabled;
            },

            isPluginLoaded: function (puglinName) {
                if (this.pluginsInstance.hasOwnProperty(puglinName)) {
                    return true;
                }

                return false;
            },

            hasError: function (pluginName) {
                if (this.loadingErrorInfos.hasOwnProperty(pluginName)) {
                    return true;
                }
                return false;
            },


            getPluginInstance: function (pluginName) {
                return this.pluginsInstance[pluginName];
            },

            init: function () {
                mediator.subscribe('on:classcontent:click', jQuery.proxy(this.clickHandler, this, this.CLICK_CONTEXT));
                /* handle context menu */
                mediator.subscribe('on:classcontent:contextmenu', jQuery.proxy(this.handleContextMenu, this));

                mediator.subscribe('on:pluginManager:loaded', jQuery.proxy(this.showContextMenu, this));
                this.init = jQuery.noop;
            },

            handleContextMenu: function (content, event) {
                event.preventDefault();
                this.clickHandler(this.RIGHT_CLICK_CONTEXT, content, event);
            },

            handleLoading: function (pluginInfos) {
                try {

                    var pluginName = pluginInfos.name,
                        pluginInstance = new this.pluginsInfos[pluginName]();

                    this.pluginsInstance[pluginInfos.completeName] = pluginInstance;
                    pluginInstance.setConfig(pluginInfos.config);
                    pluginInstance.setContext(this.context);

                    pluginInstance.onInit();

                    if (this.isScopeValid(pluginInstance) && pluginInstance.canApplyOnContext()) {
                        pluginInstance.onEnable();
                        this.handlePluginActions(pluginInstance.getActions(), pluginInfos.completeName);
                    }
                    this.loadedPluginCount = this.loadedPluginCount + 1;
                    this.checkLoadedStatus();
                } catch (e) {
                    Api.exception('PluginException', 75010, "[handleLoading] " + e);
                }
            },

            hideContextMenu: function () {
                this.contentActionWidget.cleanContextActions();
            },

            isScopeValid: function (pluginInstance) {
                var plugins = scopeInfos[this.currentScope],
                    result = true;

                if (jQuery.inArray(pluginInstance.getName(), plugins) === -1) {
                    result = false;
                }

                return result;
            },

            handleLoadingErrors: function (error) {
                this.pluginsToLoadCount = this.pluginsToLoadCount - 1;
                this.loadingErrorInfos[error.pluginName] = true;
                this.checkLoadedStatus();

                Api.exception('PluginException', 75011, "Error while loading plugin [" + error.name + "]");
            },

            /* checkload status */
            checkLoadedStatus: function () {
                if (this.pluginsToLoadCount === this.loadedPluginCount) {
                    mediator.publish("on:pluginManager:loaded", this.currentContent);
                }
            },

            showContextMenu: function () {
                if (this.eventType !== this.RIGHT_CLICK_CONTEXT) { return; }
                this.contentActionWidget.showAsContextMenu(this.getContentActions(), this.contentEvent);
            },

            clickHandler: function (eventType, content, e) {
                this.eventType = eventType;
                this.contentEvent = e;
                this.hideContextMenu();
                e.preventDefault();
                var plugins, context = {};

                if (this.currentContent === null ||
                        this.currentContent.id !== content.id ||
                        this.contentActionWidget.isBuild(content) === false ||
                        this.eventType === "on:classcontent:contextmenu"
                        ) {

                    try {
                        if (!this.isEnabled()) {
                            return;
                        }

                        context.content = content;
                        jQuery(context.content.jQueryObject).css('position', 'relative');
                        context.scope = this.currentScope;
                        context.events = [eventType];
                        this.context = context;
                        plugins = this.getContentPlugins(content.type);

                        /* exp */
                        this.pluginsToLoadCount = plugins.length;
                        this.loadedPluginCount = 0;
                        this.clearContentActions();
                        this.contentActionWidget.hide();

                        if (!plugins.length) {
                            return true;
                        }

                        this.initPlugins(plugins);
                    } catch (expect) {
                        Api.exception('PluginException', 75009, expect);
                    }

                    this.currentContent = content;
                }
            },

            resetPluginLoadInfos: function () {
                this.pluginsToLoad = 0;
                this.loadedPlugin = 0;
            },

            enablePlugins: function () {
                this.enabled = true;
            },

            reApplyPlugins: function () {
                var element = jQuery('.bb-content-selected');

                if (1 === element.length) {
                    element.click();
                }
            },

            disablePlugins: function () {
                try {
                    var pluginInstance, self = this;

                    jQuery.each(this.pluginsInstance, function (i) {
                        pluginInstance = self.pluginsInstance[i];
                        if (pluginInstance) {
                            pluginInstance.onDisable();
                        }
                    });
                } catch (e) {
                    Api.exception('PluginException', 75014, "[handleLoading] " + e);
                }

                /* hide content action */
                this.contentActionWidget.hide();
                this.disable();

                this.currentContent = null;
            },

            initPlugins: function (pluginsName) {
                var self = this,
                    pluginName,
                    pluginInstance,
                    pluginsToLoad = [];

                /* if the plugin is already loaded */
                jQuery.each(pluginsName, function (i) {
                    pluginName = pluginsName[i];

                    if (self.isPluginLoaded(pluginName)) {
                        self.loadedPluginCount = self.loadedPluginCount + 1;
                        pluginInstance = self.getPluginInstance(pluginName);
                        pluginInstance.setContext(self.context);
                        if (self.isScopeValid(pluginInstance) && pluginInstance.canApplyOnContext()) {
                            pluginInstance.onEnable();
                            self.handlePluginActions(pluginInstance.getActions(), pluginName);
                        }

                    } else if (self.hasError(pluginName)) {
                        /* no need to reload the plugin if the first attempt failed*/
                        self.pluginsToLoadCount = self.pluginsToLoadCount - 1;
                    } else {
                        pluginsToLoad.push('contentplugin!' + pluginName);
                    }
                });
                this.checkLoadedStatus();
                if (!pluginsToLoad.length) {
                    return;
                }

                /* All plugins are loaded at this stage */
                Utils.requireWithPromise(pluginsToLoad).fail(function (response) {
                    Api.exception('PluginException', '75012', " initPlugins " + response);
                });
            },

            registerActionPosition: function (pluginName, actions) {
                var actionPosition = this.getContentPlugins(this.context.content.type).indexOf(pluginName),
                    actionInfos;
                if (!this.actionsPosition.hasOwnProperty(this.context.content.type)) {
                    this.actionsPosition[this.context.content.type] = [];
                }
                actionInfos = this.actionsPosition[this.context.content.type];
                actionInfos.push({ position: actionPosition, buttons: actions });
            },

            getContentActions: function () {
                var sortedActions = [],
                    sorted = [],
                    availableActions = this.actionsPosition[this.context.content.type];
                if (availableActions.length) {
                    sorted = underscore.sortBy(availableActions, function (actionInfos) {
                        return actionInfos.position;
                    });
                    underscore(sorted).each(function (item) {
                        jQuery.merge(sortedActions, item.buttons);
                    });
                }
                return sortedActions;
            },

            clearContentActions: function () {
                this.actionsPosition[this.context.content.type] = [];
            },

            handlePluginActions: function (pluginActions, pluginName) {
                if (!this.isEnabled()) {
                    return false;
                }

                var actions = [];
                jQuery.each(pluginActions, function (i) {
                    var action = pluginActions[i];

                    if (action.hasOwnProperty("checkContext") && action.checkContext()) {
                        actions.push(action);
                    }
                });
                if (actions.length) {
                    this.registerActionPosition(pluginName, actions);
                }
                this.contentActionWidget.setContent(this.context.content.jQueryObject);
                this.contentActionWidget.appendActions(this.getContentActions(), true);
                this.contentActionWidget.show();
            },

            createPluginClass: function (def) {
                return new JS.Class(AbstractPlugin, def);
            }
        });

    return {

        scope: {
            CONTENT: "contribution.content",
            BLOCK: "contribution.block",
            PAGE: "contribution.page"
        },

        getInstance: function () {
            if (!instance) {
                instance = new PluginManager();
            }
            return instance;
        },

        registerPlugin: function (pluginName, def) {
            if (pluginsInfos.hasOwnProperty(pluginName)) {
                Api.exception('PluginManagerException', 75013, " A plugin named " + pluginName + " already exists.");
            }

            def.getName = (function (name) {
                return function () {
                    return name;
                };
            }(pluginName));

            if (!def.hasOwnProperty("scope")) {
                def.scope = [this.scope.BLOCK, this.scope.CONTENT];
            }

            var pluginScope = (jQuery.isArray(def.scope)) ? def.scope : [def.scope];

            def.getScope = (function (scope) {
                return function () {
                    return scope;
                };
            }(pluginScope));

            pluginsInfos[pluginName] = this.getInstance().createPluginClass(def);

            /* register scope here */
            jQuery.each(pluginScope, function (i) {
                var scope = pluginScope[i];

                if (!scopeInfos.hasOwnProperty(scope)) {
                    scopeInfos[scope] = [];
                }

                scopeInfos[scope].push(pluginName);
            });
        },

        AbstractPlugin: AbstractPlugin
    };
});