/*jslint unparam: true*/
define(['component!datastore', 'jquery', 'jsclass'], function (DataStore, jQuery) {
    'use strict';

    var createKWDataStore = function () {

            var KwDataStore = new DataStore.RestDataStore({
                resourceEndpoint: 'keyword'
            });

            KwDataStore.addFilter("byParent", function (value, restParams) {
                restParams.criterias.parent_uid = value;
                return restParams;
            });

            KwDataStore.moveNode = function (nodeId, data) {
                var dfd = new jQuery.Deferred(),
                    self = this;
                self.trigger("processing");

                this.restHandler.patch(this.config.resourceEndpoint, data, {
                    id: nodeId

                }).done(function () {
                    dfd.resolve(data);
                    self.trigger('dataUpdate', data);
                    self.trigger("doneProcessing");

                }).fail(function () {
                    self.trigger("doneProcessing");
                    dfd.reject.apply(this, arguments);
                });

                return dfd.promise();
            };

            return KwDataStore;
        };

    return {
        createKWDataStore: createKWDataStore
    };
});