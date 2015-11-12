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
define(
    [
        'require',
        'Core',
        'Core/Renderer',
        'jquery',
        'component!popin',
        'component!notify',
        'text!bundle/templates/admin.twig',
        'Core/DriverHandler',
        'Core/RestDriver'
    ],
    function (require, Core, Renderer, jQuery, PopinManager, Notify) {
        'use strict';

        var parent;

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            popin: {},

            bundle: {},

            tpl: '',

            restHandler: {},

            popin_config: {
                id: 'bundle-admin',
                width: window.innerWidth - 20,
                top: 180,
                height: window.innerHeight - 192,
                closeOnEscape: false,
                draggable: false,
                open: function () {
                    parent = jQuery(this).parent('.ui-dialog:first');
                    parent.css({
                        top: 192
                    });
                }
            },

            notifyUser: function (notifications) {
                var key;
                for (key in notifications) {
                    if (notifications.hasOwnProperty(key)) {
                        Notify[notifications[key].type](notifications[key].message);
                    }
                }
            },

            sendRequest: function (method, link, params) {
                var self = this;

                this.handler[method](link, params).then(
                    function (response) {
                        if (response.notification) {
                            self.notifyUser(response.notification);
                        }
                        self.updateContent(response.content);
                    },
                    function (reason) {
                        reason = JSON.parse(reason);
                        if (reason.notification) {
                            self.notifyUser(reason.notification);
                        }
                        Core.exception.silent(
                            reason.error.name,
                            reason.error.message,
                            reason.error.code,
                            {
                                phpStack: reason.error.php_stack
                            }
                        );
                        self.popin.unmask();
                    }
                );
            },

            triggerLink: function (event) {
                var target = jQuery(event.currentTarget),
                    link = target.attr('href'),
                    method = target.attr('data-http-method');
                this.sendRequest(method, link);
            },

            triggerSubmit: function (event) {
                var target = jQuery(event.target),
                    link = target.attr('action'),
                    method = target.attr('data-http-method'),
                    params = target.serializeArray(),
                    postParams = {};

                jQuery.each(params, function (key) {
                    if (params[key].name.match(/\[\]/)) {
                        if (postParams[params[key].name] === undefined) {
                            postParams[params[key].name] = [];
                        }
                        postParams[params[key].name].push(params[key].value);
                    } else {
                        postParams[params[key].name] = params[key].value;
                    }
                });

                this.sendRequest(method, link, postParams);
            },

            bindAction: function () {
                var self = this;
                jQuery('#bundle-admin-popin [data-bundle="link"]').click(function (event) {
                    event.preventDefault();
                    self.popin.mask();
                    self.triggerLink(event);
                });
                jQuery('#bundle-admin-popin [data-bundle="form"]').submit(function (event) {
                    event.preventDefault();
                    self.popin.mask();
                    self.triggerSubmit(event);
                });

            },

            updateContent: function (content) {
                content = Renderer.render(this.tpl, {content: content});
                this.popin.setContent(content);
                this.popin.unmask();
                this.bindAction();
            },

            initRestHandler: function () {
                this.handler = require('Core/DriverHandler');
                this.handler.addDriver('rest', require('Core/RestDriver'));
            },

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data) {
                this.tpl = require('text!bundle/templates/admin.twig');
                this.bundle = data.bundle;
                this.popin = PopinManager.createPopIn(this.popin_config);
                this.popin.setTitle(this.bundle.name + ' administration');
                this.initRestHandler();

                this.popin.display();
                this.popin.mask();
                this.sendRequest('read', this.bundle.admin_entry_point);
            },

            display: function () {
                this.popin.display();
                this.bindAction();
            },

            destruct: function () {
                PopinManager.destroy(this.popin);
            }
        });
    }
);