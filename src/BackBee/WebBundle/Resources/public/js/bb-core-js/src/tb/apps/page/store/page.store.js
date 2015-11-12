define(['component!datastore', 'jsclass'], function (DataStore) {
    'use strict';
    var restDataStore = new DataStore.RestDataStore({
            resourceEndpoint: 'page'
        }),
        filters = {},

        restoreOnNextFilter = false,

        computeKey = function (name) {
            var key = '',
                i;
            name = name.split('-');

            for (i = 0; i < name.length; i = i + 1) {
                key = key + name[i].charAt(0).toUpperCase() + name[i].substr(1);
            }

            return 'by' + key;
        },

        updateFilters = function (restParams, key, value) {
            if (restoreOnNextFilter) {
                var filter;
                if (!filters.hasOwnProperty('state')) {
                    restDataStore.unApplyFilter('byStatus');
                }
                for (filter in filters) {
                    if (filters.hasOwnProperty(filter)) {
                        restDataStore.applyFilter(computeKey(filter), filters[filter]);
                    }
                }
                restParams.criterias = filters;
                restoreOnNextFilter = false;
            }
            restParams.criterias[key] = value;
        };

    restDataStore.clearFilters = function () {
        filters = {};
        restDataStore.clear();
    };

    restDataStore.on('unApplyFilter:byParent', function () {
        if (!restoreOnNextFilter) {
            delete filters.parent_uid;
        }
    });

    restDataStore.on('unApplyFilter:byStatus', function () {
        if (!restoreOnNextFilter) {
            delete filters.state;
        }
    });
    /**
     * Filters definition
     */
    restDataStore.addFilter('byTrash', function (value, restParams) {
        restParams.criterias.state = value;
        restoreOnNextFilter = true;
        restDataStore.unApplyFilter('byParent');
        return restParams;
    });

    restDataStore.addFilter('byParent', function (value, restParams) {
        updateFilters(restParams, 'parent_uid', value);
        return restParams;
    });

    restDataStore.addFilter('byOffset', function (value, restParams) {
        updateFilters(restParams, 'level_offset', value);
        return restParams;
    });

    restDataStore.addFilter('byLayout', function (value, restParams) {
        restParams.criterias.layout_uid = value;
        return restParams;
    });

    restDataStore.addFilter('byTitle', function (value, restParams) {
        restParams.criterias.title = value;
        return restParams;
    });

    restDataStore.addFilter('byStatus', function (value, restParams) {
        updateFilters(restParams, 'state', value);
        return restParams;
    });

    restDataStore.addFilter('byBeforeDate', function (value, restParams) {
        restParams.criterias.before_modified = value;
        return restParams;
    });

    restDataStore.addFilter('byAfterDate', function (value, restParams) {
        restParams.criterias.after_modified = value;
        return restParams;
    });

    /**
     *  Sorters definitions
     */
    restDataStore.addSorter('byTitle', function (value, restParams) {
        restParams.sorters.title = value;
        return restParams;
    });

    restDataStore.addSorter('byStatus', function (value, restParams) {
        restParams.sorters.status = value;
        return restParams;
    });

    restDataStore.addSorter('byLayout', function (value, restParams) {
        restParams.sorters.layout = value;
        return restParams;
    });

    restDataStore.addSorter('byModified', function (value, restParams) {
        restParams.sorters.modified = value;
        return restParams;
    });

    restDataStore.applyNamedSorter = function (name, direction) {
        restDataStore.unApplySorter('byTitle');
        restDataStore.unApplySorter('byStatus');
        restDataStore.unApplySorter('byLayout');
        restDataStore.unApplySorter('byModified');
        restDataStore.applySorter(computeKey(name), direction);
    };

    restDataStore.isTrashFilter = function () {
        return restoreOnNextFilter;
    };

    restDataStore.computeKey = computeKey;

    return restDataStore;
});