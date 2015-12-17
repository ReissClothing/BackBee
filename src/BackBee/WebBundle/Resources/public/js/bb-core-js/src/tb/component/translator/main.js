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

define('tb.component/translator/main', ['component!logger', 'jquery', 'Core'], function (Logger, jQuery, Core) {
    'use strict';

    /*
     * BackBee Translator component
     * Allow to set multiples dictionnary files
     *
     * Can be accessed throught templates using global function 'trans'
     * Ex: <h1>{{ trans('hello_world') }}</h1> will return "Hello world"
     *
     * // /i18n/en_US/global.js
     * {
     *      'hello_world' : 'Hello world'
     * }
     *
     * @author Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
     */
    var Translator = {

            /**
             * Initialize Of Translator
             * @param {Object} config
             */
            init: function (config) {
                this.config = config;
                this.base = this.config.base;
                this.default_locale = config.default_locale;
                this.catalogs = {};
                this.defineDefaultLocale();

                Core.set('trans', this.translate.bind(this));
            },

            /**
             * Define a locale according to current choice or browser
             */
            defineDefaultLocale: function () {
                var locale = localStorage.getItem('locale');

                if (null === locale) {
                    locale = navigator.language;

                    if (-1 !== locale.indexOf('-')) {
                        locale = locale.replace('-', '_');
                    } else {
                        locale = locale + '_' + locale.toUpperCase();
                    }
                }

                this.setLocale(locale);
            },

            /**
             * Return a current locale
             * @returns {String}
             */
            getLocale:  function () {
                return this.locale;
            },

            /**
             * Set the locale
             * @param {String} locale
             */
            setLocale: function (locale) {
                this.locale = locale;

                localStorage.setItem('locale', locale);
            },

            /**
             * Return a default locale
             * @returns {String}
             */
            getDefaultLocale: function () {
                return this.defaut_locale;
            },

            /**
             * Translate the key from the json file
             *
             * Translate with the current locale
             * else translate with the default locale
             * else show the key
             *
             * @param {String} key
             * @returns {String}
             */
            translate: function (key) {
                var catalog = this.getCatalog(this.locale),
                    translation;

                if (undefined !== catalog) {
                    translation = catalog[key];
                }

                if (undefined === translation) {
                    Logger.notice('The key "' + key + '" has not translation.');
                    translation = key;
                }

                return translation;
            },

            /**
             * Return a catalog corresponding with a current locale
             * @param {String} locale
             * @returns {Object}
             */
            getCatalog: function (locale) {
                if (undefined === locale) {
                    return undefined;
                }

                return this.catalogs[locale];
            },

            /**
             * Load the file of a catalog
             * @param {String} locale
             */
            loadCatalog: function (locale) {
                var self = this,
                    dfd = jQuery.Deferred(),
                    catalog = this.getCatalog(locale);

                if (undefined === locale) {
                    locale = this.locale;
                }

                if (undefined !== catalog) {
                    this.setLocale(locale);
                    dfd.resolve();
                }

                jQuery.ajax({
                    'url': self.base + locale + '/global.json',
                    'data': 'json',
                    'async': false
                }).done(function (response) {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }

                    self.catalogs[locale] = response;
                    self.setLocale(locale);

                    dfd.resolve();
                }).fail(function (response) {
                    if (404 === response.status) {
                        self.loadCatalog(self.config.default_locale).done(function () {
                            dfd.resolve();
                        });
                    }
                });

                return dfd.promise();
            }
        };

    return Translator;
});
