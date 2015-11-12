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

define(['jquery', 'jsclass'], function (jQuery) {
    'use strict';

    /**
     * ContextMenu object
     */
    var ContextMenu = new JS.Class({

        /**
         * Initialize of contextmenu
         * @param {Object} userConfig
         */
        initialize: function (userConfig) {

            var defaultSettings = {
                contentSelector : null,
                menuActions : [],
                menuCls : "bb5-context-menu",
                actionBuilder : null,
                domTag: 'body'
            };

            this.isEnabled = false;
            this.template = jQuery("<div><ul></ul></div>").clone();
            this.contextMenu = null;
            this.contextMenuTarget = null;
            this.beforeShow = null;

            this.settings = jQuery.extend(true, defaultSettings, userConfig);
            if (this.settings.hasOwnProperty('defaultItemBuilder') && typeof this.settings.defaultItemBuilder === 'function') {
                this.defaultItemBuilder = this.settings.defaultItemBuilder;
            }
            if (this.settings.menuActions.length) {
                this.contextMenu = this.buildContextmenu();
            }
            this.beforeShow = (typeof this.settings.beforeShow === "function") ? jQuery.proxy(this.settings.beforeShow, this) : jQuery.noop;
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            var self = this,
                body = jQuery('body');

            jQuery(this.settings.contentSelector).on("contextmenu", jQuery.proxy(this.show, this));
            body.on('contextmenu', function () {
                self.hide();
                return true;
            });

            body.on('click', function () {
                self.hide();
                return true;
            });
        },

        /**
         * Apply filters to contextmenu
         * @param {Object} filters
         */
        applyFilters: function (filters) {
            var self = this,
                filter,
                key;

            filters = (jQuery.isArray(filters)) ? filters : [];

            jQuery(this.contextMenu).find("li").show();

            for (key in filters) {
                if (filters.hasOwnProperty(key)) {
                    filter = filters[key];
                    jQuery(self.contextMenu).find("." + filter).eq(0).parent().hide(); //hide li
                }
            }
        },

        addMenuItem: function (menuItem) {
            this.settings.menuActions.push(menuItem);
            this.contextMenu = this.buildContextmenu();
        },

        defaultItemBuilder: function (btnInfo) {
            var self = this,
                btnWrapper = jQuery("<li></li>").clone(),
                btnTpl = jQuery("<button></button>");

            jQuery(btnTpl).addClass(btnInfo.btnCls).text(btnInfo.btnLabel);

            jQuery(btnTpl).bind("click", function (e) {
                btnInfo.btnCallback.call(this, e, self.contextMenuTarget, btnInfo.btnType);
                self.hide();
                return false;
            });

            jQuery(btnTpl).attr("data-type", btnInfo.btnType);
            jQuery(btnTpl).appendTo(jQuery(btnWrapper));

            return btnWrapper;
        },

        buildContextmenu: function () {
            var self = this,
                linksContainer = document.createDocumentFragment();

            jQuery(this.template).addClass(this.settings.menuCls);

            jQuery.each(this.settings.menuActions, function (btnType, item) {
                item.btnType = btnType;
                item = self.defaultItemBuilder(item);

                linksContainer.appendChild(jQuery(item).get(0));
            });

            jQuery(this.template).find('ul').html(linksContainer);
            jQuery(this.template).hide().appendTo(jQuery(this.settings.domTag));

            return jQuery(this.template);
        },

        show: function (e) {

            if (!this.isEnabled) {
                return false;
            }

            this.beforeShow(e.currentTarget);

            e.preventDefault();
            e.stopPropagation();
            var position = {
                left: e.clientX,
                top: e.clientY
            };

            jQuery(this.contextMenu).css({
                position: "absolute",
                left: position.left + "px",
                top: position.top + "px"
                //zIndex: jQuery(e.currentTarget).zIndex()+1
            });

            this.contextMenuTarget = jQuery(e.currentTarget);
            this.applyFilters(this.filters);
            this.resetFilters();

            if (!this.hasVisibleItems()) {
                return this.hide();
            }

            jQuery(this.contextMenu).show();
        },


        resetFilters: function () {
            this.filters = [];
        },

        hasVisibleItems: function () {
            var showMenu = false,
                li,
                item;
            li = jQuery(this.contextMenu).find("li");

            jQuery(li).each(function (i) {
                item = li[i];
                if (jQuery(item).css('display') !== 'none') {
                    showMenu = true;
                    return false;
                }
            });

            return showMenu;
        },

        /**
         * Hide contextmenu
         */
        hide: function () {
            jQuery(this.contextMenu).hide();
        },

        /**
         * Set disable contextmenu
         */
        disable: function () {
            this.isEnabled = false;
            jQuery(this.contextMenu).hide();
        },

        /**
         * Set enable contextmenu
         */
        enable: function () {
            this.isEnabled = true;
        },

        /**
         * Set filters
         * @param {Object} filters
         */
        setFilters: function (filters) {
            this.filters = (jQuery.isArray(filters)) ? filters : [];
        },

        addFilter: function (key) {
            if (!jQuery.isArray(this.filters)) {
                this.filters = [];
            }
            if (typeof key === "string") {
                this.filters.push(key);
            }
        }
    });

    return ContextMenu;
});
