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
        'component': 'bundles/backbeeweb/js/bb-core-js/src/tb/component/component',
        'filter': 'bundles/backbeeweb/js/bb-core-js/src/tb/filter/filter',
        'Core': 'bundles/backbeeweb/js/bb-core-js/bower_components/backbee-core-js/dist/Core',
        'jsclass' : 'bundles/backbeeweb/js/bb-core-js/node_modules/jsclass/min/core',
        'underscore': 'bundles/backbeeweb/js/bb-core-js/bower_components/underscore/underscore',
        'nunjucks': 'bundles/backbeeweb/js/bb-core-js/bower_components/nunjucks/browser/nunjucks',
        'BackBone': 'bundles/backbeeweb/js/bb-core-js/bower_components/backbone/backbone',
        'text': 'bundles/backbeeweb/js/bb-core-js/bower_components/requirejs-text/text',
        'moment': 'bundles/backbeeweb/js/bb-core-js/bower_components/moment/moment',
        'URIjs': 'bundles/backbeeweb/js/bb-core-js/bower_components/uri.js/src',
        'URIjs/URI': 'bundles/backbeeweb/js/bb-core-js/bower_components/uri.js/src/URI',
        'bootstrap-carousel': 'bundles/backbeeweb/js/bb-core-js/bower_components/bootstrap/js/carousel',
        'bootstrap-dropdown': 'bundles/backbeeweb/js/bb-core-js/bower_components/bootstrap/js/dropdown',
        'ckeeditor': 'bundles/backbeeweb/js/bb-core-js/bower_components/ckeeditor/ckeditor',
        'dropzone': 'bundles/backbeeweb/js/bb-core-js/bower_components/dropzone/dist/dropzone',

        'jquery.noconflict': 'bundles/backbeeweb/js/bb-core-js/src/core-jquery.noconflict',
        //@todo gvf is jquery min necessary?
        'core-jquery': 'bundles/backbeeweb/js/bb-core-js/bower_components/jquery/dist/jquery.min',
        'jqueryui': 'bundles/backbeeweb/js/bb-core-js/bower_components/jquery-ui/jquery-ui',
        'jquery-helper': 'bundles/backbeeweb/js/bb-core-js/src/jquery.helper',
        'jquery-layout' : 'bundles/backbeeweb/js/bb-core-js/bower_components/jquery.layout/dist/jquery.layout-latest',
        'lib.jqtree': 'bundles/backbeeweb/js/bb-core-js/bower_components/jqtree/tree.jquery',
        'datetimepicker': 'bundles/backbeeweb/js/bb-core-js/bower_components/datetimepicker/jquery.datetimepicker',
        'jssimplepagination': 'bundles/backbeeweb/js/bb-core-js/bower_components/jssimplepagination/jquery.simplePagination',

        'cryptojs.core': 'bundles/backbeeweb/js/bb-core-js/bower_components/cryptojslib/components/core',
        'cryptojs.md5': 'bundles/backbeeweb/js/bb-core-js/bower_components/cryptojslib/components/md5'
    },
    // @todo gvf this shouldn't be needed with bundle symfonys override
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
    },
    deps: ['bundles/backbeeweb/js/toolbar/src/tb/init'],
    callback: function (init) {
        'use strict';
        init.listen();
    }
});

//require.config({ baseUrl: document ? document.getElementById('bb5-ui').getAttribute('data-base-url') + "resources/toolbar/" : './resources/toolbar/'});
require.config({ baseUrl: '/'});

