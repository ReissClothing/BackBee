define(['Core'], function (Core) {
    'use strict';

    /**
     * Register every routes of contribution application into Core.routeManager
     */
    Core.RouteManager.registerRoute('contribution', {
        prefix: 'contribution',
        routes: {
            'index': {
                url: '/index',
                action: 'MainController:index'
            },

            'media-library': {
                url: '/medialibrary',
                action: 'MainController:showMediaLibrary'
            }
        }
    });
});
