define(['Core', 'jquery', 'Core/Renderer', 'text!../searchengine/templates/layout.tpl', 'jsclass', 'datetimepicker'], function (Core, jQuery, Renderer, layout) {
    'use strict';

    var SimpleSearchEngine = new JS.Class({
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
            this.widget = jQuery(Renderer.render(layout)).clone();
            this.initDatepicker();
            this.bindEvents();
        },

        initDatepicker: function () {
            var self = this;
            this.widget.find(this.config.datepickerFieldClass).datetimepicker({
                timepicker: false,
                closeOnDateSelect: true,
                format: "d/m/Y",
                parentID: self.mainSelector,
                onSelectDate: function (ct, field) {
                    jQuery(field).data('selectedTime', Math.ceil(ct.getTime() / 1000));
                }
            });
        },

        showOrInitDateTimePicker: function (e) {
            var dateField = jQuery(e.currentTarget).parents(".col-bb5-22").find(this.defaultConfig.datepickerFieldClass).eq(0);
            if (!jQuery(dateField).data('datetimepicker')) {
                this.initDatepicker();
            }
            jQuery(dateField).datetimepicker('show');
        },

        bindEvents: function () {
            jQuery(this.widget).on('click', this.config.datepickerClass, jQuery.proxy(this.showOrInitDateTimePicker, this));
            jQuery(this.widget).on('click', this.config.searchBtnClass, jQuery.proxy(this.handleSearch, this));
            jQuery(this.widget).on('keyup', ".form-control", jQuery.proxy(this.handleKeyUp, this));
        },

        render: function (container, positionMethod) {
            positionMethod = (typeof positionMethod === "string") ? positionMethod : 'html';
            if (container && jQuery(container).length) {
                jQuery(container)[positionMethod](this.widget);
            } else {
                return this.widget;
            }
        },

        handleKeyUp: function (e) {
            var field = jQuery(e.currentTarget),
                value = field.val(),
                fieldName = field.data("fieldname");
            if (!value.length) {
                if (field.hasClass(this.config.datepickerFieldCls)) {
                    field.data('selectedTime', '');
                }
                this.trigger("resetField", fieldName);
            }
        },

        reset: function () {
            jQuery(this.widget).find("input").val("");
            jQuery(this.widget).find(this.config.beforeDateClass).eq(0).data("selectedTime", "");
            jQuery(this.widget).find(this.config.afterDateClass).eq(0).data("selectedTime", "");
        },

        handleSearch: function () {
            var criteria = {};
            criteria.title = jQuery(this.config.titleFieldClass).eq(0).val();
            criteria.beforeDate = jQuery(this.config.beforeDateClass).eq(0).data('selectedTime') || '';
            criteria.afterDate = jQuery(this.config.afterDateClass).eq(0).data('selectedTime') || '';
            this.trigger("doSearch", criteria);
        }
    });
    return {
        createSimpleSearchEngine: function (config) {
            config = config || {};
            return new SimpleSearchEngine(config);
        },
        SimpleSearchEngine: SimpleSearchEngine
    };
});