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
        'page.routes': 'src/tb/apps/page/routes',

        //Repositories
        'page.repository': 'src/tb/apps/page/repository/page.repository',
        'layout.repository': 'src/tb/apps/page/repository/layout.repository',

        //store
        'page.store': 'src/tb/apps/page/store/page.store',

        //Controllers
        'page.main.controller': 'src/tb/apps/page/controllers/main.controller',

        //Forms
        'page.abstract.form': 'src/tb/apps/page/forms/AbstractPage.form',
        'page.form': 'src/tb/apps/page/forms/Page.form',

        //Widgets
        'page.widget.InformationPage': 'src/tb/apps/page/widgets/InformationPage',

        //Components
        'page.save.manager': 'src/tb/apps/page/components/SaveManager',

        //Views
        'page.view.contribution.index': 'src/tb/apps/page/views/page.view.contribution.index',
        'page.view.delete': 'src/tb/apps/page/views/page.view.delete',
        'page.view.create': 'src/tb/apps/page/views/page.view.create',
        'page.view.edit': 'src/tb/apps/page/views/page.view.edit',
        'page.view.new': 'src/tb/apps/page/views/page.view.new',
        'page.view.clone': 'src/tb/apps/page/views/page.view.clone',
        'page.view.manage': 'src/tb/apps/page/views/page.view.manage',
        'page.view.manage.item': 'src/tb/apps/page/views/page.view.manage.item',
        'page.view.tree': 'src/tb/apps/page/views/page.view.tree',
        'page.view.tree.contribution': 'src/tb/apps/page/views/page.view.tree.contribution',
        'page.view.tree.management': 'src/tb/apps/page/views/page.view.tree.management',
        'page.view.toolbar': 'src/tb/apps/page/views/page.view.toolbar',
        'page.view.tree.select.parent': 'src/tb/apps/page/views/page.view.tree.select.parent',
        'page.view.validation': 'src/tb/apps/page/views/page.view.validation',

        //Templates
        'page/tpl': 'src/tb/apps/page/templates',
        'page/tpl/contribution/index': 'src/tb/apps/page/templates/contribution.index.twig',
        'page/tpl/contribution/scheduling_publication': 'src/tb/apps/page/templates/contribution-scheduling.dialog.twig',
        'page/widgets/tpl/information_page': 'src/tb/apps/page/widgets/templates/information-page.twig'

    }
});

define('app.page', ['Core', 'page.widget.InformationPage'], function (Core, InformationPage) {

    'use strict';

    var popins = {};

    /**
     * page application declaration
     */
    Core.ApplicationManager.registerApplication('page', {

        getPopins: function () {
            return popins;
        },

        onInit: function () {
            Core.set('application.page', this);

            Core.Scope.subscribe('page', function () {
                if (Core.Scope.isOpen('contribution')) {
                    Core.ApplicationManager.launchApplication('contribution');
                    InformationPage.show();
                }
            }, function () {
                if (Core.Scope.isOpen('contribution')) {
                    var popin;
                    InformationPage.hide();

                    for (popin in popins) {
                        if (popins.hasOwnProperty(popin)) {
                            popins[popin].hide();
                        }
                    }
                }
            });
        }
    });

});
