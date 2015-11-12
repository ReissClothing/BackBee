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
define(['Core', 'bundle.view.list', 'bundle.view.index'], function (Core, ListView, IndexView) {
    'use strict';

    var trans = Core.get('trans') || function (value) {return value; };

    Core.ControllerManager.registerController('MainController', {

        appName: 'bundle',

        config: {
            imports: ['bundle.repository'],
            define: {
                confirmService: ['bundle/views/confirm.view', 'jquery'],
                adminService: ['bundle/views/admin.view']
            }
        },

        /**
         * Initialize of Bundle Controller
         */
        onInit: function () {
            this.bundles = null;
            this.repository = require('bundle.repository');
            this.mainApp =  Core.get('application.main');
        },

        /**
         * Index action
         * Show the first bundle in toolbar
         */
        indexAction: function () {
            var config = {};

            Core.ApplicationManager.invokeService('main.main.setTitlePane', trans('plugins'));

            if (this.indexShown !== true) {
                config.force = true;
                config.bindEvents = true;

                this.renderView(IndexView, config);
            }

            this.listAndRender(IndexView, config);
        },

        adminService: function (req, bundle) {
            var View = req('bundle/views/admin.view'),
                adminView = Core.get('current_admin_view'),
                currentBundle = Core.get('current_bundle');

            if (undefined === adminView || currentBundle !== bundle) {
                adminView = new View({bundle: bundle});
                Core.set('current_admin_view', adminView);
                Core.set('current_bundle', bundle);
            }

            adminView.display();
        },

        /**
         * List service
         * Show all bundle in toolbar
         */
        listService: function () {
            if (undefined === this.bundles) {
                this.listAndRender(ListView);
            } else {
                this.renderView(ListView, {'bundles': this.bundles});
            }
        },

        /**
         * Call the driver handler for return the list and
         * use the view for render the html
         *
         * @param {Object} ConstructorView
         * @returns {bundle.controller_L1.bundle.controllerAnonym$1}
         */
        listAndRender: function (ConstructorView, config) {
            var self = this;

            if (config === undefined) {
                config = {};
            }

            this.repository.listManageable().done(function (data) {
                config.bundles = data;

                self.bundles = config.bundles;

                self.renderView(ConstructorView, config);
                self.indexShown = true;
            });
        },

        /**
         * Call the view for render data in the DOM
         * @param {Object} ConstructorView
         * @param {Object} datas
         */
        renderView: function (ConstructorView, config) {
            var view = new ConstructorView(config);
            view.render();
        },


        confirmService: function (req, target, bundleId, action) {
            var self = this,
                jQuery = req('jquery'),
                View = req('bundle/views/confirm.view'),
                view = new View({action: action ? 'enable' : 'disable'});

            target = jQuery(target);

            view.display().then(
                function () {
                    target.siblings('a').removeClass('active');
                    target.addClass('active');
                    self.repository.active(action, bundleId);
                    view.destruct();
                },
                function () {
                    view.destruct();
                }
            );
        }
    });
});
