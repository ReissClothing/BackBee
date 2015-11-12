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

define(['Core/Utils', 'cryptojs.md5', 'jsclass'], function (Utils, CryptoJS) {
    'use strict';

    return new JS.Class({
        ude: 'undefined',

        initialize: function () {
            this.data = {
                groups: []
            };

            this.map = [
                'id',
                'email',
                'login',
                'picture',
                'firstname',
                'lastname',
                'status',
                'password',
                'activated',
                'api_key_enabled',
                'groups',
                'api_key'
            ];
        },

        populate: function (values) {
            var key;

            for (key in values) {
                if (values.hasOwnProperty(key) && -1 !== this.map.indexOf(key)) {
                    this.data[key] = values[key];
                }
            }
        },

        getObject: function () {
            return this.data;
        },

        id: function () {
            return this.data.id;
        },

        login: function () {
            return this.data.login === undefined ? this.ude : this.data.login;
        },

        email: function () {
            return this.data.email === undefined ? this.ude : this.data.email;
        },

        activated: function () {
            return (this.data.activated !== undefined && this.data.activated !== false) ? true : false;
        },

        api_key_enabled: function () {
            return (this.data.api_key_enabled !== undefined && this.data.api_key_enabled !== false) ? true : false;
        },

        picture: function () {
            return this.data.picture === undefined ?
                    'http://www.gravatar.com/avatar/' + CryptoJS.MD5(this.email().toLowerCase()) + '?s=45&r=pg&d=mm' :
                    this.data.picture;
        },

        firstname: function () {
            return this.data.firstname === undefined ? this.ude : this.data.firstname;
        },

        lastname: function () {
            return this.data.lastname === undefined ? this.ude : this.data.lastname;
        },

        fullName: function () {
            return this.firstname() + ' ' + this.lastname();
        },

        groups: function () {
            return Utils.castAsArray(this.data.groups || []);
        },

        api_key: function () {
            return this.data.api_key === undefined ? this.ude : this.data.api_key;
        }
    });
});
