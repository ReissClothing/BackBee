define([
    'Core/Renderer',
    'text!page/tpl/toolbar.twig',
    'jquery',
    'datetimepicker'
], function (Renderer, template, jQuery) {
    'use strict';

    /**
     * View of new page
     * @type {Object} Backbone.View
     */
    return Backbone.View.extend({
        /**
         * Initialize of PageViewReview
         */
        initialize: function (data) {
            this.pageStore = data.pageStore;
        },
        /**
         * Render the template into the DOM with the Renderer
         * @returns {Object} PageViewReview
         */
        render: function (layouts) {
            return Renderer.render(template, {layouts: layouts});
        },

        unApplyFilters: function () {
            this.pageStore.unApplyFilter('byStatus');
            this.pageStore.unApplyFilter('byTitle');
            this.pageStore.unApplyFilter('byLayout');
            this.pageStore.unApplyFilter('byBeforeDate');
            this.pageStore.unApplyFilter('byAfterDate');
        },

        initDatepicker: function () {
            var parent = jQuery('#bb-page-manage-toolbar');

            parent.find('.bb-datepicker').datetimepicker({
                timepicker: false,
                closeOnDateSelect: true,
                format: 'd/m/Y',
                parentID: 'bb-page-manage-toolbar',
                onSelectDate: function (ct, field) {
                    jQuery(field).data('selectedTime', Math.ceil(ct.getTime() / 1000));
                }
            });
        },

        clearFilters: function () {
            this.pageStore.clearFilters();
            this.pageStore.applyFilter('byStatus', [0, 1, 2, 3]);
            this.pageStore.applySorter('byModified', 'desc');
            this.pageStore.execute();
        },

        search: function (event) {
            var values = jQuery(event.target).serializeArray();

            event.preventDefault();
            this.unApplyFilters();

            values.forEach(function (data) {
                if (data.name === 'status') {
                    if (this.pageStore.isTrashFilter()) {
                        return true;
                    }
                    if (data.value === 'all') {
                        this.pageStore.applyFilter('byStatus', [0, 1, 2, 3]);

                    } else if (data.value === 'all_with_trash') {
                        this.pageStore.applyFilter('byStatus', [0, 1, 2, 3, 4]);

                    } else if (data.value === 'all_active') {
                        this.pageStore.applyFilter('byStatus', [1, 3]);

                    } else if (data.value === 'all_inactive') {
                        this.pageStore.applyFilter('byStatus', [0, 2]);

                    } else if (data.value === 'trash') {
                        this.pageStore.applyFilter('byStatus', [4]);
                    }
                } else if (data.value !== '' && !(data.name === 'layout' && data.value === 'all')) {
                    this.pageStore.applyFilter(this.pageStore.computeKey(data.name), data.value);
                }
            }.bind(this));

            this.pageStore.unApplyFilter('byOffset');

            this.pageStore.execute();
        },

        bindEvents: function () {
            jQuery('#toolbar-page-clear-filter-action').click(this.clearFilters.bind(this));

            jQuery('#bb-toolbar-page-search-form').submit(this.search.bind(this));

            this.initDatepicker();
            jQuery('.bb-datepicker').datetimepicker('show');
        }
    });

});