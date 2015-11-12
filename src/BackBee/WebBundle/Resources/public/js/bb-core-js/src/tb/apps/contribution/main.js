require.config({
    paths: {
        'contribution.routes': 'src/tb/apps/contribution/routes',
        'contribution.main.controller': 'src/tb/apps/contribution/controllers/main.controller',

        //Views
        'contribution.view.index': 'src/tb/apps/contribution/views/contribution.view.index',

        //Templates
        'contribution/tpl/index': 'src/tb/apps/contribution/templates/index.twig'
    }
});

define('app.contribution', ['Core'], function (Core) {
    'use strict';

    var popins = {};

    /**
     * Contribution application declaration
     */
    Core.ApplicationManager.registerApplication('contribution', {

        getPopins: function () {
            return popins;
        },

        onInit: function () {
            Core.set('application.contribution', this);

            Core.Scope.subscribe('contribution', function () {
                return;
            }, function () {
                var popin;
                for (popin in popins) {
                    if (popins.hasOwnProperty(popin) && popins[popin] !== undefined) {
                        popins[popin].hide();
                    }
                }
            });
        }
    });
});
