/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */
require.config({
    paths: {
        'bundle': 'src/tb/apps/bundle',
        'bundle.routes': 'src/tb/apps/bundle/routes',
        'bundle.main.controller': 'src/tb/apps/bundle/controllers/main.controller',
        'bundle.repository': 'src/tb/apps/bundle/repository/bundle.repository',

        //Views
        'bundle.view.list': 'src/tb/apps/bundle/views/bundle.view.list',
        'bundle.view.index': 'src/tb/apps/bundle/views/bundle.view.index',

        //Templates
        'bundle/tpl/list': 'src/tb/apps/bundle/templates/list.twig',
        'bundle/tpl/index': 'src/tb/apps/bundle/templates/index.twig'
    }
});

define('app.bundle', ['Core', 'jquery'], function (Core, jQuery) {
    'use strict';

    /**
     * bundle application declaration
     */
    Core.ApplicationManager.registerApplication('bundle', {

        onStart: function () {
            Core.Scope.register('bundle');
        },

        onResume: function () {
            Core.Scope.register('bundle');
        },

        onStop: function () {
            jQuery('#extensions').dialog('close');
        }
    });

});
