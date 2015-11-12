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

define(['Core', 'jquery', 'BackBone', 'component!keywordselector', 'Core/Renderer'], function (Core, jQuery, BackBone, KeywordSelector, Renderer) {
    "use strict";
    var KeywordView = BackBone.View.extend({

        root: "<div/>",

        initialize: function (template, formTag, element) {
            this.el = formTag;
            this.template = template;
            this.element = element;
            this.keywordSelector = KeywordSelector.createSelector(this.parseConfig());
            this.bindEvents();
            this.isRendered = false;
        },

        parseConfig : function () {
            var config = {},
                keywords = (this.element.config && this.element.config.hasOwnProperty("value")) ? this.element.config.value : [];

            config.keywords = keywords;

            if (this.element.config && this.element.config.hasOwnProperty('maxentry')) {
                config.maxentry = this.element.config.maxentry;
            }

            this.previousValue = (Array.isArray(config.keywords)) ? config.keywords : [];
            return config;
        },

        bindEvents: function () {
            var self = this,
                keywordContainer;
            this.keywordSelector.on("change", this.handleChange.bind(this));
            Core.Mediator.subscribe('on:form:render', function (form) {
                if (self.isRendered) { return true; }
                keywordContainer = form.find('.element_' + self.element.getKey());
                self.inputTag = jQuery(keywordContainer).find(".form-infos").eq(0);
                self.inputTag.val(JSON.stringify(self.previousValue));
                self.keywordSelector.render(keywordContainer);
                self.isRendered = true;
            });
        },


        handleChange: function (keywords) {
            var result = [],
                keywordItem,
                item;
            jQuery.each(keywords, function (i) {
                keywordItem = keywords[i];
                item = {};
                item.uid = keywordItem.uid;
                item.keyword = keywordItem.keyword;
                result.push(item);
            });
            result = JSON.stringify(result);
            if (JSON.stringify(this.previousValue) !== JSON.stringify(result)) {
                this.inputTag.attr("updated", "true");
                jQuery(this.inputTag).val(result);
                return;
            }
            /* reset */
            this.inputTag.val("[]");
            this.inputTag.removeAttr("updated");
        },

        getKeywords: function () {
            this.keywordSelector = [];
        },

        render: function () {
            return Renderer.render(this.template, {element: this.element});
        }
    });

    return KeywordView;

});