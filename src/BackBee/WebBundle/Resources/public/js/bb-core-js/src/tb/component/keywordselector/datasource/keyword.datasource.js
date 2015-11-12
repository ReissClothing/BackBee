/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


/*jslint unparam: true*/
define(['component!datastore', 'jsclass'], function (DataStore) {
    'use strict';

    var restDataStore = new DataStore.RestDataStore({
        resourceEndpoint: 'keyword',
        rewriteResourceEndpoint: function (type, params) {
            if (type === "delete") {
                return "keyword/" + params.type;
            }
        }
    });

    restDataStore.addFilter("byKeyword", function (value, restParams) {
        restParams.criterias.term = value;
        return restParams;
    });

    return restDataStore;
});