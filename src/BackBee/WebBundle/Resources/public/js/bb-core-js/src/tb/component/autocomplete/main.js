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

/*jshint regexp:true */
/*jslint browser: true */

define(['Core', 'BackBone', 'Core/Utils', 'jquery', 'Core/Renderer', 'text!../autocomplete/templates/layout.html', 'jsclass'], function (Core, BackBone, Utils, jQuery, Renderer, layout) {

    "use strict";

    var AutoComplete = new JS.Class({

        settings: {
            dataSource: [],
            selectedCls: "active",
            threshold: 3,
            field: null
        },

        initialize: function (userSettings) {

            this.widget = jQuery("<div/>").clone();
            jQuery.extend(this, {}, BackBone.Events);
            this.settings = jQuery.extend({}, this.settings, userSettings);
            this.suggestionList = new Utils.SmartList({
                idKey: "uid"
            });
            this.suggestionList.onInit = this.updateUi.bind(this);
            this.handleDataSource();
            this.threshold = parseInt(this.settings.threshold, 10) || 3;
            this.lastRequest = null;
            this.currentSelection = null;
            this.field = jQuery(this.settings.field);
            this.isPending = false;
            jQuery(this.field).css('position', 'relative');

            jQuery(this.widget).addClass("backbee-autocomplete");
            this.field.after(this.widget);
            this.clear();
            this.bindEvents();
        },

        handleDataSource: function () {

            if (typeof this.settings.dataSource === "function") {
                this.autoCompleteHandler = this.settings.dataSource;
                return;
            }

            if (typeof this.dataSource === "string") {
                this.autoCompleteHandler = this.urlSourceHandler;
                return;
            }

            if (Array.isArray(this.dataSource)) {
                this.autoCompleteHandler = this.localSourceHandler;
            }

        },

        /* deal with ajaxDataSource */
        urlSourceHandler: function (value) {
            return jQuery.ajax({
                url: this.dataSource + "?term=" + value
            });
        },

        /* deal with array DataSource */
        localSourceHandler: function (value) {
            var dfd = new jQuery.Deferred(),
                data = null,
                cleanedValue = jQuery.trim(value),
                matcher = new RegExp('^' + cleanedValue + 'i');
            data = jQuery.grep(this.dataSource, function (keyword) {
                return matcher.test(keyword);
            });
            dfd.resolve(data);
            return dfd;
        },

        updateUi: function (data) {
            var list = Renderer.render(layout, {
                suggestions: Utils.castAsArray(data)
            });
            this.widget.html(list);
            jQuery(this.widget).show();
            this.isPending = false;
        },

        updateSuggestion: function (data) {
            if (Array.isArray(data) && data.length) {
                this.suggestionList.reset();
                this.suggestionList.setData(data);
            }
        },

        getSelected: function () {
            return this.currentSelection;
        },


        findSuggestions: function (e) {
            if (e.keyCode === 13) {
                return;
            }
            this.clear();
            this.isPending = true;
            var value = jQuery.trim(jQuery(e.target).val()),
                self = this,
                onReady = this.updateSuggestion.bind(this);

            if (value.length < this.threshold) {
                return false;
            }

            setTimeout(function () {
                if (self.lastRequest && self.lastRequest.state() === "pending") {
                    self.trigger("abort");
                    if (typeof self.lastRequest.reject === "function") {
                        self.lastRequest.reject();
                    }
                }
                self.lastRequest = self.autoCompleteHandler(value);
                if (typeof self.lastRequest.done !== 'function') {
                    Core.exception('AutoCompleteException', 87000, '[findSuggestions] autoCompleteHandler should return a Promise object. ');
                }
                self.lastRequest.done(onReady);
            }, 100);
        },

        handleClick: function (e) {
            var suggestionNode = jQuery(e.target),
                uid = jQuery(suggestionNode).data("item");
            this.trigger("selection", this.suggestionList.get(uid));

            this.clear();
        },

        clear: function () {
            jQuery(this.widget).html("").hide();
            this.currentSelection = null;
        },

        bindEvents: function () {
            if (this.dataStore && typeof this.dataStore.isA === "function") {
                this.dataStore.on("stateUpdate", this.updateUi.bind(this));
            }
            this.widget.on("mouseenter", ".suggestion-item", this.handleSelection.bind(this));
            this.field.on("keyup", this.handleEnterKey.bind(this));
            this.field.on("keyup", this.findSuggestions.bind(this));
            this.widget.on("click", ".suggestion-item", this.handleClick.bind(this));
        },

        handleEnterKey: function (e) {
            if (e.keyCode !== 13) {
                return;
            }
            e.preventDefault();
            if (this.currentSelection) {
                this.trigger("selection", this.currentSelection);
                this.clear();
                return true;
            }

            if (!this.isPending) {
                this.trigger("empty");
            }

            return true;
        },

        handleSelection: function (e) {
            if (e.type === "mouseenter") {
                jQuery(e.target).addClass(this.settings.selectedCls);
                this.currentSelection = this.suggestionList.get(jQuery(e.target).data("item"));
            }
        },

        applyTo: function (inputField) {
            if (!inputField) {
                return;
            }
            jQuery(inputField).attr("backbee-autocomplete");
        }

    });

    return {
        createAutoComplete: function (config) {
            return new AutoComplete(config || {});
        },

        AutoComplete: AutoComplete
    };

});