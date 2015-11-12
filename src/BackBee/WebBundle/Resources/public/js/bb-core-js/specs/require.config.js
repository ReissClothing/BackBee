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
require.config({
    baseUrl: './',
    urlArgs: 'cb=' + Math.random(),
    paths: {
        'component': 'src/tb/component/component',
        'filter': 'src/tb/filter/filter',
        'vendor': 'dist/vendor.min',
        'ckeeditor': 'dist/ckeeditor/ckeditor',

        'jasmine': 'node_modules/grunt-contrib-jasmine/vendor/jasmine-2.0.0/jasmine',
        'jasmine-html': 'node_modules/grunt-contrib-jasmine/vendor/jasmine-2.0.0/jasmine-html',
        'es5-shim': 'bower_components/es5-shim',

        'spec': 'specs/'
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
            deps: ['underscore', 'jquery'],
            exports: 'Backbone'
        },
        Core: {
            deps: ['BackBone', 'jquery', 'jsclass', 'underscore', 'nunjucks', 'URIjs/URI']
        },
        bootstrapjs: {
            deps: ['jquery']
        },

        'jquery-layout': {
            deps: ['jquery']
        },
        'cryptojs.core': {
            exports: 'CryptoJS'
        },
        'cryptojs.md5': {
            deps: ['cryptojs.core'],
            exports: 'CryptoJS'
        },
        jasmine: {
            exports: 'jasmine'
        },
        'jasmine-html': {
            deps: ['jasmine'],
            exports: 'jasmine'
        }
    },
    deps: ['vendor']
});
