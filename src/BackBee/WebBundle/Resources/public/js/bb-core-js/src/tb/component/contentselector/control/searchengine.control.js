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
        'Core',
        'require',
        'nunjucks',
        'jquery',
        'jsclass',
        'datetimepicker',
        'text!cs-templates/searchengine.tpl'
    ],
    function (Core, require, nunjucks, jQuery) {
        'use strict';

        var ContentSearchEngine = new JS.Class({

            mainSelector: Core.get('wrapper_toolbar_selector'),

            defaultConfig: {
                datepickerClass: '.show-calendar',
                datepickerFieldClass: '.bb5-datepicker',
                datepickerFieldCls: 'bb5-datepicker',
                beforeDateClass: '.before-date',
                afterDateClass: '.after-date',
                titleFieldClass: '.content-title',
                searchBtnClass: '.search-btn'
            },

            initialize: function (config) {
                this.config = jQuery.extend({}, this.defaultConfig, config);
                jQuery.extend(this, {}, Backbone.Events);
                this.widget = jQuery(nunjucks.renderString(require('text!cs-templates/searchengine.tpl'), {}));
                this.initDatepicker();
                this.topPosition = null;
                this.bindEvents();
            },

            initDatepicker: function () {
                var self = this,
                    field,
                    fields = jQuery(this.config.datepickerFieldClass);
                jQuery.each(fields, function (i) {
                    field = jQuery(fields[i]);
                    field.data("field-no", i);
                    self.attachDatePicker(field);
                });
            },

            attachDatePicker: function (field) {
                var self = this;
                field.datetimepicker({
                    timepicker: false,
                    closeOnDateSelect: true,
                    format: "d/m/Y",
                    parentID: this.mainSelector,
                    onSelectDate: function (ct, field) {
                        jQuery(field).data('selectedTime', Math.ceil(ct.getTime() / 1000));
                    },

                    onShow: function (date, field) {
                        var datePicker = jQuery(field).data("xdsoft_datetimepicker");
                        jQuery(datePicker).hide();
                        return date;
                    },

                    onGenerate: function (date, field) {
                        var datePicker = jQuery(field).data("xdsoft_datetimepicker");
                        jQuery(datePicker).css("top", self.topPosition + "px");
                        return date;
                    }
                });
            },

            showOrInitDateTimePicker: function (e) {
                var dateField = jQuery(e.currentTarget).parent().siblings(this.defaultConfig.datepickerFieldClass).eq(0);
                if (!jQuery(dateField).data('datetimepicker')) {
                    this.initDatepicker();
                }
                jQuery(dateField).datetimepicker('show');
                this.topPosition = e.clientY + 15;
            },

            bindEvents: function () {
                jQuery(this.widget).on('click', this.config.datepickerClass, jQuery.proxy(this.showOrInitDateTimePicker, this));
                jQuery(this.widget).on('click', this.config.searchBtnClass, jQuery.proxy(this.handleSearch, this));
                jQuery(this.widget).on('keyup', ".form-control", jQuery.proxy(this.handleKeyUp, this));
            },

            handleKeyUp: function (e) {
                var field = jQuery(e.currentTarget),
                    value = field.eq(0).val(),
                    fieldName = field.data("fieldname");
                    /* reset datetime */
                if (field.hasClass(this.config.datepickerFieldCls)) {
                    field.data("selectedTime", "");
                }
                if (!value.length) {
                    this.trigger("onResetField", fieldName);
                }
            },

            render: function (container, positionMethod) {

                positionMethod = (typeof positionMethod === "string") ? positionMethod : 'html';
                if (container && jQuery(container).length) {
                    jQuery(container)[positionMethod](this.widget);
                } else {
                    return this.widget;
                }
            },

            reset: function () {
                var fields = jQuery(this.widget).find("input"),
                    self = this,
                    field;
                jQuery(this.widget).find("input").val("");
                jQuery(this.widget).find(this.config.beforeDateClass).eq(0).data("selectedTime", "");
                jQuery(this.widget).find(this.config.afterDateClass).eq(0).data("selectedTime", "");
                fields.each(function (i) {
                    field = fields[i];
                    self.trigger("onResetField", jQuery(field).data("fieldname"));
                });
            },

            handleSearch: function () {
                var criteria = {};
                criteria.title = jQuery(this.widget).find(this.config.titleFieldClass).eq(0).val();
                criteria.beforeDate = jQuery(this.widget).find(this.config.beforeDateClass).eq(0).data('selectedTime') || '';
                criteria.afterDate = jQuery(this.widget).find(this.config.afterDateClass).eq(0).data('selectedTime') || '';
                this.trigger("doSearch", criteria);
            }
        });
        return {
            createSearchEngine: function (config) {
                config = config || {};
                return new ContentSearchEngine(config);
            },
            ContentSearchEngine: ContentSearchEngine
        };
    }
);
