(function () {
    'use strict';
    define(['Core'], function (Core) {
        return {
            load: function (adapterName, req, onload) {
                try {
                    var adapterConf = Core.config("plugins:core:rte:config" + ":" + adapterName),
                        instance;
                    req(['component!' + adapterName], function (RTEAdapter) {
                        instance = new RTEAdapter(adapterConf).onInit();
                        onload(instance);
                    }, function (reason) {
                        Core.Mediator.publish("on:rteManager:rteLoadedError", reason);
                        onload.error(reason);
                    });
                } catch (e) {
                    Core.Mediator.publish("on:rteManager:rteLoadedError", e);
                }
            }
        };
    });
}());
