/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
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
        'Core',
        'jquery',
        'Core/Renderer',
        'tb.component/treeview/TreeView',
        'tb.component/popin/main',
        'text!treeview.templates/popin.twig'
    ],
    function (Core, jQuery, Renderer, TreeViewMng, PopInMng, popinTemplate) {

        'use strict';

        var PopInTreeview = new JS.Class({

                mainSelector: Core.get('wrapper_toolbar_selector'),

                defaultConfig: {
                    height: 300,
                    width: 350,
                    title: 'popin title',
                    autoDisplay: true,
                    position: "left"
                },

                initialize: function (options) {
                    this.options = jQuery.extend({}, this.defaultConfig, options);

                    this.isLoaded = false;
                    this.checkParameters();

                    this.widget = this.popinTemplate = jQuery(Renderer.render(popinTemplate)).clone();

                    this.popIn = this.createPopIn();
                    this.popIn.setContent(this.popinTemplate);
                    this.popIn.addOption("create", jQuery.proxy(this.handleCreate, this));
                    this.popIn.addOption("open", jQuery.proxy(this.initOnOpen, this));

                    if (this.options.hasOwnProperty("autoDisplay") && this.options.autoDisplay) {
                        this.display();
                    }
                },

                handleCreate: function () {
                    if (typeof this.options.onCreate === 'function') {
                        this.options.onCreate.call(this);
                    }
                },

                /**
                 * Binding action event
                 * @param {String} actionClass
                 * @param {Function} func
                 * @param {Object} context
                 */
                on: function (event, actionClass, func, context) {

                    context = context || null;

                    if (typeof event === "string" && typeof func === 'function') {
                        this.popinTemplate.on(event, actionClass, jQuery.proxy(func, context));
                    }
                },

                showFilter: function () {
                    jQuery(this.popinTemplate).find(".folder-filter").removeClass("hidden").show();
                },

                hideFilter: function () {
                    jQuery(this.popinTemplate).find(".folder-filter").addClass("hidden").hide();
                },

                getPopIn: function () {
                    return this.popIn;
                },

                getTreeView: function () {
                    return this.treeView;
                },

                checkParameters: function () {
                    this.options.open = (typeof this.options.open === "function") ? jQuery.proxy(this.options.open, this) : jQuery.noop;
                    this.options.create = (typeof this.options.create === "function") ? jQuery.proxy(this.options.create, this) : jQuery.noop;

                    if (!this.options.hasOwnProperty("data") || !Array.isArray(this.options.data)) {
                        this.options.data = [];
                    }
                },

                initTree: function () {
                    var treeWrapper = this.popinTemplate.find('.bb5-treeview').eq(0);
                    this.treeView = TreeViewMng.createTreeView(treeWrapper, this.options);
                },

                initOnOpen: function () {

                    if (this.isLoaded) {
                        return;
                    }
                    this.initTree();

                    this.options.open();
                    this.isLoaded = true;
                },

                createPopIn: function () {
                    PopInMng.init(this.mainSelector);

                    return PopInMng.createPopIn(this.options);
                },

                display: function () {
                    this.popIn.display();
                },

                hide: function () {
                    this.popIn.hide();
                }
            });

        return {
            createPopInTreeView: function (options) {
                return new PopInTreeview(options);
            }
        };
    }
);
