(function () {
    'use strict';
    define(['Core'], function (Core) {
        return {
            load: function (pluginName, req, onload) {

                var namespaceInfos = Core.config('plugins:namespace'),
                    namespace = namespaceInfos.core,
                    pluginPaths = pluginName.split(':'),
                    pname,
                    pluginInfos,
                    pluginConf = {},
                    pluginFullPath;

                if (pluginPaths.length > 1) {
                    namespace = namespaceInfos[pluginPaths[0]];
                    pname = pluginPaths.pop();
                } else {
                    pname = pluginPaths.shift();
                }

                pluginFullPath = namespace + pname + '/main';
                pluginConf = Core.config('plugins:' + pluginPaths[0] + ':' + pname);
                /* plugin is registered here */
                pluginInfos = {
                    name: pname,
                    completeName: pluginName,
                    namespace: namespace,
                    path: pluginFullPath,
                    config: pluginConf.config || {}
                };
                req([pluginFullPath], function () {
                    Core.Mediator.publish('on:pluginManager:loading', pluginInfos);
                    onload(pluginInfos);
                }, function (reason) {
                    pluginInfos.error = true;
                    Core.Mediator.publish('on:pluginManager:loadingErrors', { pluginName: pluginName, reason: reason });
                    onload(pluginInfos); //Handle error nicely
                });
            }
        };
    });
}());