/*jslint unparam: true*/
define(['component!datastore', 'jsclass'], function (DataStore) {
    'use strict';
    var restDataStore = new DataStore.RestDataStore({
        resourceEndpoint: 'classcontent',
        rewriteResourceEndpoint: function (type, params) {
            if (type === "delete") {
                return "classcontent/" + params.type;
            }
        }
    });

    restDataStore.addFilter("byCategory", function (value, restParams) {
        restParams.criterias.category = value;
        return restParams;
    });

    restDataStore.addFilter("byClasscontent", function (value, restParams) {
        restParams.criterias.uid = value;
        return restParams;
    });

    restDataStore.addFilter("byTitle", function (value, restParams) {
        restParams.criterias.searchField = value;
        return restParams;
    });

    restDataStore.addFilter("bySite", function (value, restParams) {
        restParams.criterias.site_uid = value;
        return restParams;
    });

    restDataStore.addFilter("byBeforeDate", function (value, restParams) {
        restParams.criterias.beforePubdateField = value;
        return restParams;
    });

    restDataStore.addFilter("byAfterDate", function (value, restParams) {
        restParams.criterias.afterPubdateField = value;
        return restParams;
    });

    return restDataStore;
});