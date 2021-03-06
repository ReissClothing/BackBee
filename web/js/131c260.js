/*jslint unparam: true*/
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
require.onResourceLoad = function (context, map) {
    "use strict";
    if (map.name === "jquery.noconflict") {
        require.undef(map.name);
    }
};
define('vendor', ['jquery-helper'], function (jqHelper) {
    'use strict';
    jqHelper.restoreCoreJQuery();
});

define('hook', function () {

    'use strict';

    return {
        'hooks': [],
        'register': function (func) {
            if (document.hasOwnProperty('bb_core')) {
                if ('function' === typeof func) {
                    func(require('Core'));
                }
            } else {
                this.hooks.push(func);
            }
        },
        'execute': function (core) {
            var key;

            for (key in this.hooks) {
                if (this.hooks.hasOwnProperty(key)) {
                    if ('function' === typeof this.hooks[key]) {
                        this.hooks[key](core);
                        delete this.hooks[key];
                    }
                }
            }
        }
    };
});

require.config({
    catchError: true,
    waitSeconds: 15,
    urlArgs: 'cb=' + Math.random(),
    paths: {
        'component': 'src/tb/component/component',
        'filter': 'src/tb/filter/filter',
        'Core': 'bower_components/backbee-core-js/dist/Core',
        'jsclass' : 'node_modules/jsclass/min/core',
        'underscore': 'bower_components/underscore/underscore',
        'nunjucks': 'bower_components/nunjucks/browser/nunjucks',
        'BackBone': 'bower_components/backbone/backbone',
        'text': 'bower_components/requirejs-text/text',
        'moment': 'bower_components/moment/moment',
        'URIjs': 'bower_components/uri.js/src',
        'URIjs/URI': 'bower_components/uri.js/src/URI',
        'bootstrap-carousel': 'bower_components/bootstrap/js/carousel',
        'bootstrap-dropdown': 'bower_components/bootstrap/js/dropdown',
        'ckeeditor': 'bower_components/ckeeditor/ckeditor',
        'dropzone': 'bower_components/dropzone/dist/dropzone',

        'jquery.noconflict': 'src/core-jquery.noconflict',
        'core-jquery': 'bower_components/jquery/dist/jquery.min',
        'jqueryui': 'bower_components/jquery-ui/jquery-ui',
        'jquery-helper': 'src/jquery.helper',
        'jquery-layout' : 'bower_components/jquery.layout/dist/jquery.layout-latest',
        'lib.jqtree': 'bower_components/jqtree/tree.jquery',
        'datetimepicker': 'bower_components/datetimepicker/jquery.datetimepicker',
        'jssimplepagination': 'bower_components/jssimplepagination/jquery.simplePagination',

        'cryptojs.core': 'bower_components/cryptojslib/components/core',
        'cryptojs.md5': 'bower_components/cryptojslib/components/md5'
    },
    'map': {
        "*": {
            'jquery': 'core-jquery'
        },
        'core-jquery': {
            'jquery': 'jquery'
        }
    },

    'shim': {
        'lib.jqtree': {
            deps: ['jquery.noconflict']
        },
        "core-jquery": {
            init: function () {
                "use strict";
                return window.$.noConflict(true);
            }
        },

        underscore: {
            exports: '_'
        },

        BackBone: {
            deps: ['underscore', 'jquery.noconflict'],
            exports: 'Backbone'
        },
        Core: {
            deps: ['BackBone', 'jquery.noconflict', 'jsclass', 'underscore', 'nunjucks', 'URIjs/URI']
        },
        'bootstrap-carousel': {
            deps: ['jquery.noconflict']
        },
        'bootstrap-dropdown': {
            deps: ['jquery.noconflict']
        },
        'jquery-layout': {
            deps: ['jquery.noconflict']
        },
        'cryptojs.core': {
            exports: 'CryptoJS'
        },
        'cryptojs.md5': {
            deps: ['cryptojs.core'],
            exports: 'CryptoJS'
        }
    //},
    //deps: ['src/tb/init'],
    //callback: function (init) {
    //    'use strict';
    //    init.listen();
    }
});

//require.config({ baseUrl: document ? document.getElementById('bb5-ui').getAttribute('data-base-url') + "resources/toolbar/" : './resources/toolbar/'});


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

define(function () {
    'use strict';

    var selector = document.querySelector('[data-toolbar-selector="true"]'),

        init = {

            toolBarDisplayed: false,

            configUri: 'toolbar/config',

            listen: function () {
                if (!selector.hasAttribute('data-autostart')) {
                    window.addEventListener('keydown', this.manageAccess.bind(this));
                } else {
                    this.load();
                }
            },

            manageAccess: function (event) {
                if (!this.toolBarDisplayed) {
                    if (
                        (event.code === 'keyB' || event.which === 66) &&
                            event.ctrlKey &&
                            event.altKey
                    ) {
                        this.load(true);
                    }
                }
            },

            load: function (removeSession) {
                var self = this,
                    loader = document.getElementById('backbee-loader');

                loader.classList.add('visible');
                require(['vendor'], function () {
                    require(['Core', 'component!session', 'jquery', 'hook'], function (Core, session, jQuery) {

                        if (removeSession === true) {
                            session.destroy();
                        }

                        Core.set('session', session);
                        Core.set('is_connected', session.isAuthenticated());
                        Core.set('wrapper_toolbar_selector', '#' + selector.id);
                        Core.set('api_base_url', selector.getAttribute('data-api'));

                        document.bb_core = true;
                        require('hook').execute(Core);

                        require(
                            [
                                'Core/DriverHandler',
                                'Core/RestDriver',
                                'Core/Renderer',
                                'component!translator'
                            ],
                            function (DriverHandler, RestDriver, Renderer, Translator) {
                                loader.classList.remove('visible');

                                RestDriver.setBaseUrl(Core.get('api_base_url'));
                                DriverHandler.addDriver('rest', RestDriver);

                                /* we need the filter to be able to use the login form before load
                                 * the application configuration
                                 */
                                Renderer.addFilter('trans', function (key) { return key; });

                                var initOnConnect = function () {
                                        DriverHandler.read(self.configUri).done(function (config) {
                                            Core.initConfig(config);

                                            Translator.init(Core.config('component:translator'));
                                            Translator.loadCatalog().done(function () {

                                                Renderer.addFunction('trans', jQuery.proxy(Translator.translate, Translator));
                                                Renderer.addFilter('trans', jQuery.proxy(Translator.translate, Translator));

                                                require(['component!exceptions-viewer'], {});
                                            });
                                        });

                                    },
                                    router = null;

                                Core.ApplicationManager.on('routesLoaded', function () {
                                    /*cf http://backbonejs.org/#Router for available options */
                                    router = Core.RouteManager.initRouter({silent: true});
                                });

                                Core.ApplicationManager.on('appIsReady', function (app) {
                                    router.navigate(app.getMainRoute());
                                });

                                if (session.isAuthenticated()) {
                                    initOnConnect();
                                }
                            },
                            self.onError
                        );
                    }, self.onError);
                });
            },

            onError: function (error) {
                console.log(error);
            }
        };
    return init;
});
