/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

require.config({
    paths: {
        'user': 'src/tb/apps/user',
        'user.routes': 'src/tb/apps/user/routes',
        'user.main.controller': 'src/tb/apps/user/controllers/main.controller',
        'user.user.controller': 'src/tb/apps/user/controllers/user.controller',
        'user.group.controller': 'src/tb/apps/user/controllers/group.controller'
    }
});

define('app.user', ['Core', 'user/views/toolbar.view'], function (Core, View) {
    'use strict';

    /**
     * user application declaration
     */
    Core.ApplicationManager.registerApplication('user', {

        popin: {},
        toolbar: {},

        initToolbar: function () {
            this.toolbar = new View({app: this});
        },

        onInit: function () {
            Core.set('application.user', this);
        },

        onStart: function () {
            Core.Scope.register('user');
            this.initToolbar();
        },

        onResume: function () {
            Core.Scope.register('user');
            this.initToolbar();
        },

        onStop: function () {
            this.popin.popinManager.destroy(this.popin.popin);
            this.popin.unbindDnD();
            this.popin = null;
        }
    });
});
