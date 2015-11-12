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
require.config({
    paths: {
        'dataviewTemplate': 'src/tb/component/dataview/templates',
        'dataStore': 'src/tb/component/dataview/DataStore'
    }
});
define(
    [
        'require',
        'underscore',
        'Core',
        'BackBone',
        'jquery',
        'jsclass',
        'text!dataviewTemplate/layout.tpl'
    ],
    function (require, underscore) {
        'use strict';
        var mainTpl = require('text!dataviewTemplate/layout.tpl'),
            jQuery = require('jquery'),
            Core = require('Core'),
            BaseDataView = new JS.Class({
                LIST_MODE: 'list',
                GRID_MODE: 'grid',
                defaultConfig: {
                    cls: 'data-view',
                    css: {
                        width: "450px",
                        height: "400px"
                    },
                    itemKey: 'uid',
                    itemCls: 'data-view-item',
                    itemSelectedCls: 'selected',
                    rendererClass: '',
                    renderMode: 'list',
                    renderAsCollection: false,
                    enableSelection: true,
                    allowMultiSelection: true,

                    customItemEvents: {},
                    itemRenderer: function () {
                        return '<p>An item renderer must be provided</p>';
                    }
                },

                initialize: function (config) {
                    jQuery.extend(this, {}, Backbone.Events);
                    this.config = jQuery.extend({}, this.defaultConfig, config);
                    this.itemKey = this.config.itemKey;
                    this.renderMode = this.config.renderMode;
                    this.build();
                    this.renderers = {};
                    this.buildDefaultRenderers();
                    this.itemRenderer = this.config.itemRenderer;
                    this.dataWrapper = jQuery(this.widget).find('.data-wrapper').eq(0);
                    this.handleCustomItemEvents();
                    this.selectionInfos = [];
                    this.data = {};
                    this.bindEvents();
                },

                handleCustomItemEvents: function () {
                    var self = this;
                    if (jQuery.isEmptyObject(this.config.customItemEvents)) {
                        return;
                    }
                    jQuery.each(this.config.customItemEvents, function (customEvent, data) {
                        if (!data.hasOwnProperty('evt') || data.hasOwnProperty('selector')) {
                            return true;
                        }
                        jQuery(self.widget).on(data.evt, data.selector, function (e) {
                            var itemNode = jQuery(e.target).closest(self.itemCls);
                            self.trigger(customEvent, e, jQuery(itemNode).data('item-data'));
                        });
                    });
                },

                buildDefaultRenderers: function () {
                    var listRenderer = {
                        name: "list",
                        render: function (items) {
                            var wrapper = jQuery("<ul/>");
                            wrapper.addClass("bb5-list-media bb5-list-media-is-list clearfix");
                            return jQuery(wrapper).html(items);
                        }
                    },
                        gridRenderer = {
                            name: "grid",
                            render: function (items) {
                                var wrapper = jQuery("<ul/>");
                                wrapper.addClass("bb5-list-media bb5-list-media-is-grid clearfix");
                                return jQuery(wrapper).html(items);
                            }
                        };
                    try {
                        this.registerRenderer(listRenderer);
                        this.registerRenderer(gridRenderer);
                    } catch (e) {
                        Core.exception('BaseDataViewException', 46897, e);
                    }
                    /* register renderer */
                },
                bindEvents: function () {
                    jQuery(this.widget).on("click", "." + this.config.itemCls, jQuery.proxy(this.handleItemClick, this));
                    if (this.config.dataStore && typeof this.config.dataStore.on === "function") {
                        this.config.dataStore.on("dataStateUpdate", jQuery.proxy(this.setData, this));
                    }
                },

                disableSelection: function () {
                    this.config.enableSelection = false;
                    this.cleanSelection();
                },

                enableSelection: function () {
                    this.config.enableSelection = true;
                },

                handleItemClick: function (e) {
                    if (!this.config.enableSelection) { return false; }
                    var target = jQuery(e.currentTarget),
                        data = target.data('item-data'),
                        selectionItem = {},
                        itemKey = data[this.itemKey];

                    if (target.hasClass(this.config.itemSelectedCls)) {
                        target.removeClass(this.config.itemSelectedCls);
                        /* remove item from selection */
                        this.selectionInfos = underscore.reject(this.selectionInfos, function (selection) { return selection.id === itemKey; });
                        this.trigger("itemUnselected", data, target);
                        return;
                    }
                    selectionItem.id = itemKey;
                    selectionItem.item = data;
                    this.selectionInfos.push(selectionItem);

                    if (!this.config.allowMultiSelection) {
                        this.cleanSelection();
                    }
                    jQuery(target).addClass(this.config.itemSelectedCls);
                    this.trigger("itemSelected", data, target);
                    return true;
                },

                /* build the component */
                build: function () {
                    this.widget = jQuery(mainTpl).clone();
                    jQuery(this.widget).addClass(this.defaultConfig.cls);
                    jQuery(this.widget).css(this.config.css);
                },

                setData: function (data) {
                    this.data = data;
                    this.updateUi();
                },

                updateUi: function () {
                    var items = (this.renderAsCollection) ? this.data : this.renderItems(),
                        renderer = this.getModeRenderer(this.renderMode).render(items);
                    jQuery(this.dataWrapper).html(renderer);
                    this.showSelections();
                    this.trigger('afterRender');
                },

                getModeRenderer: function (mode) {
                    var rendererName = mode + 'Renderer',
                        renderer;
                    if (!this.renderers.hasOwnProperty(rendererName)) {
                        Core.exception('BaseDataViewException', 46897, '[getModeRenderer] "' + rendererName + '" can\'t be found.');
                    }
                    renderer = this.renderers[rendererName];
                    if (typeof renderer.render !== "function") {
                        Core.exception('BaseDataViewException', 46898, '[getModeRenderer] renderer "' + rendererName + '" should provide a render function');
                    }
                    return this.renderers[mode + 'Renderer'];
                },

                registerRenderer: function (renderer) {
                    if (jQuery.isEmptyObject(renderer)) {
                        Core.exception('BaseDataViewException', 46898, '[registerRenderer] renderer must be an object and should not be empty');
                    }
                    if (!renderer.hasOwnProperty('name') || typeof renderer.name !== 'string') {
                        Core.exception('BaseDataViewException', 46899, '[registerRenderer] renderer must have a name "' + JSON.stringify(renderer));
                    }
                    if (!renderer.hasOwnProperty("render") && typeof renderer.render !== "function") {
                        Core.exception('BaseDataViewException', 46900, '[registerRenderer] must provided a render function');
                    }
                    this.renderers[renderer.name + 'Renderer'] = renderer;
                },

                genId: (function () {
                    var i = 0;
                    return function (prefix) {
                        prefix = (typeof prefix === 'string') ? prefix + '_' : '';
                        i = i + 1;
                        return prefix + i;
                    };
                }()),

                renderItems: function () {
                    var self = this,
                        ctn = document.createDocumentFragment();
                    jQuery.each(this.data, function (i, item) {
                        var itemRender = jQuery(self.itemRenderer(self.renderMode, item));
                        if (!itemRender || itemRender.length === 0) {
                            Core.exception('BaseDataViewException', 50002, '[renderItems] InvalidAppConfig [appPath] key is missing');
                        }
                        jQuery(itemRender).data("view-item", self.genId("item"));
                        jQuery(itemRender).data("item-data", item);
                        jQuery(itemRender).data("item-no", i);
                        jQuery(itemRender).attr("data-uid", item[self.itemKey]);
                        jQuery(itemRender).addClass(self.config.itemCls);

                        ctn.appendChild(jQuery(itemRender).get(0));
                    });
                    return ctn;
                },

                setRenderMode: function (mode) {
                    var selections = this.getSelection();
                    this.renderMode = mode;
                    this.updateUi();
                    this.selectItems(selections);
                },

                render: function (container) {
                    if (container && jQuery(container).length) {
                        jQuery(container).html(this.widget);
                        return;
                    }
                    return this.widget;
                },

                getSelection: function () {
                    var result = [],
                        selection;
                    jQuery.each(this.selectionInfos, function (i) {
                        selection = this.selectionInfos[i];
                        result.push(selection.item);
                    }.bind(this));
                    result = underscore.compact(result);
                    return result;
                },

                cleanSelection: function () {
                    this.dataWrapper.find("." + this.config.itemCls).removeClass(this.config.itemSelectedCls);
                    this.selectionInfos = [];
                },

                reset: function () {
                    this.cleanSelection();
                    this.setData({});
                },

                showSelections: function () {
                    var selection,
                        selector,
                        item,
                        self = this;
                    jQuery.each(this.selectionInfos, function (i) {
                        selection = self.selectionInfos[i];
                        selector = '[data-uid="' + selection[self.itemKey] + '"]';
                        item = self.dataWrapper.find(selector);
                        if (item.length) {
                            jQuery(item).addClass(self.config.itemSelectedCls);
                            selection.item = jQuery(item).data("item-data");
                        }
                    });
                },

                selectItems: function (items) {
                    var self = this,
                        item;
                    items = (jQuery.isArray(items)) ? items : [items];
                    jQuery.each(items, function (i) {
                        item = items[i];
                        if (!underscore.findWhere(self.selectionInfos, {id : item[self.config.itemKey]})) {
                            self.selectionInfos.push({id : item[self.config.itemKey], item : item});
                        }
                    });
                    this.showSelections();
                }
            });
        return {
            createDataView: function (config) {
                return new BaseDataView(config);
            },
            DataView: BaseDataView
        };
    }
);
