/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


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

/*jslint browser: true */


define(['Core/Utils',
    'Core/Renderer',
    'component!autocomplete',
    'BackBone',
    'text!../keywordselector/templates/layout.html',
    'text!../keywordselector/templates/items.html',
    '../keywordselector/datasource/keyword.datasource',
    'jquery',
    'jsclass'], function (Utils, Renderer, AutoComplete, BackBone, layout, listTpl, kwDataSource, jQuery) {

    "use strict";

    var KeywordSelector = new JS.Class({
        defaultConfig: {
            keywords: [],
            maxentry: 25,
            keywordCls: "kw-item",
            editMode: false,
            cls: "kw-wrapper"
        },
        SPACE_KEY: 32,
        COMMA_KEY: 188,
        ENTER_KEY: 13,
        BACKSPACE_KEY: 8,

        initialize: function (userConfig) {
            this.config = jQuery.extend({}, this.defaultConfig, userConfig);
            jQuery.extend(this, {}, BackBone.Events);
            this.kwList = new Utils.SmartList({
                idKey: "uid",
                maxEntry: parseInt(this.defaultConfig.maxentry, 10)
            });

            this.kwList.onChange = this.updateui.bind(this);
            this.kwList.onDelete = this.updateui.bind(this);
            this.widget = jQuery(layout).clone();
            this.listContainer = this.widget.find(".keyword-wrapper").eq(0);
            this.kwEditor = this.widget.find(".kw-editor").eq(0);
            if (this.config.cls) {
                jQuery(this.widget).addClass(this.config.cls);
            }

            this.lastRequest = null;
            this.kwList.setData(this.config.keywords);
            this.autoComplete = this.initAutoCompletion();
            this.kwDataSource = kwDataSource;
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            jQuery(this.widget).on("click", ".fa-remove", this.rmKwHandler.bind(this));

            jQuery(this.widget).on("keydown", ".kw-editor", this.handleBackSpace.bind(this));

            this.autoComplete.on("selection", function (suggestion) {
                var keywordItem = jQuery.extend({}, suggestion);
                keywordItem.keyword = suggestion.label;
                self.addKeyword(keywordItem);
                self.clear();
            });

            this.autoComplete.on("abort", function () {
                if (self.lastRequest) {
                    self.lastRequest.abort();
                }
            });
        },

        initAutoCompletion: function () {
            return AutoComplete.createAutoComplete({
                field: this.kwEditor,
                dataSource: this.handleAutoComplete.bind(this)
            });
        },

        cleanData: function (data) {
            jQuery.each(data, function (i) {
                data[i].label = data[i].keyword;
            });
            return data;
        },

        handleAutoComplete: function (value) {
            var dfd = new jQuery.Deferred(),
                cleanData = this.cleanData.bind(this);
            kwDataSource.applyFilter("byKeyword", value).execute().done(function (data) {
                dfd.resolve(cleanData(data));
            });
            this.lastRequest = kwDataSource.getLastRequest();
            return dfd.promise();
        },

        handleBackSpace: function (e) {
            if (e.keyCode !== this.BACKSPACE_KEY) {
                return;
            }
            var kwUid,
                self = this,
                lastKeyword = this.listContainer.children().last();

            if (jQuery.trim(this.kwEditor.val()).length !== 0) {
                return;
            }
            /* Get and remove the last keyword */
            if (lastKeyword.length !== 0 && jQuery.trim(this.kwEditor.val()).length === 0) {
                kwUid = lastKeyword.data("kw-uid");
                self.removeKeword(kwUid);
            }
            return false;
        },

        rmKwHandler: function (e) {
            var kwUid = jQuery(e.target).data("kw-uid");
            this.removeKeword(kwUid);
        },

        clear: function () {
            jQuery(this.kwEditor).val("");
        },

        getKeywords: function () {
            return this.kwList.toArray(true);
        },

        /* the single and only way to update the ui
         * this method should be called automatically when the keywords change
         **/
        updateui: function (data) {
            var cleanData = Utils.castAsArray(data),
                self = this;
            this.listContainer.html(Renderer.render(listTpl, {
                keywords: cleanData,
                delimiter: this.config.delimiter
            }));
            setTimeout(function () {
                self.kwEditor.focus();
            }, 10);
        },

        addKeyword: function (keyword) {
            this.kwList.set(keyword);
            this.trigger("change", this.getKeywords());
        },

        removeKeword: function (uid) {
            this.kwList.deleteItemById(uid);
            this.trigger("change", this.getKeywords());
        },


        addKeywords: function (keywords) {

            if (Array.isArray(keywords)) {
                this.kwList.setData(keywords);
            }
        },

        render: function (container) {
            jQuery(container).append(this.widget);
            if (!container) {
                return this.widget;
            }
        }

    });

    return {

        createSelector: function (config) {
            config = config || {};
            return new KeywordSelector(config);
        },

        KeywordSelector: KeywordSelector
    };

});