/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

define('tb.component/session/session', ['Core', 'Core/Utils', 'jsclass'], function (Core, Utils) {
    'use strict';

    var Session = new JS.Class({
        /**
         * This constant define a Key of API KEY
         * @type {String}
         */
        HEADER_API_KEY: 'X-API-KEY',

        /**
         * This constant define a Key of API SIGNATURE
         * @type {String}
         */
        HEADER_API_SIGNATURE: 'X-API-SIGNATURE',

        STORAGE_KEY: 'bb-session-auth',

        initialize: function () {
            this.key = null;
            this.signature = null;
            this.storage = window.localStorage;

            Core.Mediator.subscribe('request:send:before', this.onBeforeSend, this);
            Core.Mediator.subscribe('request:send:fail', this.onRequestFail, this);

            this.load();
            Core.Mediator.publish('on:session:start', this);
        },

        /**
         * Event
         * He check in Session storage if an item with key 'bb5-session-auth' exist and
         * add the headers to the request if he found it.
         * @param {Object} Request
         */
        onBeforeSend: function (Request) {
            if (this.isValidAuthentication()) {
                Request.addHeader(this.HEADER_API_KEY, this.key);
                Request.addHeader(this.HEADER_API_SIGNATURE, this.signature);
            }
        },

        /**
         * Event
         * Check the status of response.
         * If the user is forbidden to acces,
         * an popin will be showed with an forbidden message.
         * If the user require an authentication, an popin will be showed with
         * a authentication form
         * @param {Object} response
         */
        onRequestFail: function (response) {
            if (response.getStatus() === 403) {
                Utils.requireWithPromise(['component!notify']).then(
                    function (notify) {
                        notify.error(response.errorText);
                    }
                );

            } else if (response.getStatus() === 401) {

                this.destroy();
                Core.set('is_connected', false);
                Utils.requireWithPromise(['component!authentication']).then(
                    function (authenticate) {
                        authenticate.popin.unmask();
                        authenticate.showForm(response.errorText);
                    }
                );
            }

            return response;
        },


        persist: function () {
            var data = {
                key: this.key,
                signature: this.signature
            };
            this.storage.setItem(this.STORAGE_KEY, JSON.stringify(data));
        },

        load: function () {
            if (this.storage.hasOwnProperty(this.STORAGE_KEY)) {
                try {
                    var data = JSON.parse(this.storage.getItem(this.STORAGE_KEY));
                    this.key = data.key;
                    this.signature = data.signature;
                } catch (e) {
                    Core.exception.silent('SessionException', 500, 'Error during the session loading', {error: e});
                    return;
                }

            }
        },

        destroy: function () {
            Core.Mediator.publish('before:session:destruct', this);

            this.key = null;
            this.signature = null;

            if (this.storage.hasOwnProperty(this.STORAGE_KEY)) {
                this.storage.removeItem(this.STORAGE_KEY);
            }
        },

        setKey: function (key) {
            this.key = key;
            return this;
        },

        setSignature: function (signature) {
            this.signature = signature;
            return this;
        },

        isValidAuthentication: function () {
            if (null !== this.key && null !== this.signature) {
                return true;
            }
            return false;
        },

        isAuthenticated: function () {
            var a = this.isValidAuthentication();
            var b = document.getElementById('bb5-ui');
            var c = document.getElementById('bb5-ui').hasAttribute('data-autostart');
            if (a && b && c) {
                return true;
            }
            return false;
        }
    });

    return new Session();
});