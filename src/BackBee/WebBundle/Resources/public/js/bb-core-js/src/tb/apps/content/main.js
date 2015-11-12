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
        'app.content': 'src/tb/apps/content',

        //Controllers
        'content.main.controller': 'src/tb/apps/content/controllers/main.controller',

        //Routing
        'content.routes': 'src/tb/apps/content/routes',

        //Repositories
        'content.repository': 'src/tb/apps/content/repository/content.repository',
        'revision.repository': 'src/tb/apps/content/repository/revision.repository',
        'resource.repository': 'src/tb/apps/content/repository/resource.repository',
        'keyword.repository': 'src/tb/apps/content/repository/keyword.repository',

        //Components
        'content.manager': 'src/tb/apps/content/components/ContentManager',
        'content.dnd.manager': 'src/tb/apps/content/components/DndManager',
        'content.mouseevent.manager': 'src/tb/apps/content/components/MouseEventManager',
        'content.save.manager': 'src/tb/apps/content/components/SaveManager',
        'content.container': 'src/tb/apps/content/components/ContentContainer',
        'definition.manager': 'src/tb/apps/content/components/DefinitionManager',
        'content.breadcrumb': 'src/tb/apps/content/components/Breadcrumb',
        'content.pluginmanager': 'src/tb/apps/content/components/PluginManager',

        //Widgets
        'content.widget.DialogContentsList': 'src/tb/apps/content/widgets/DialogContentsList',
        'content.widget.Breadcrumb': 'src/tb/apps/content/widgets/Breadcrumb',
        'content.widget.Edition': 'src/tb/apps/content/widgets/Edition',

        //Models
        'content.models.AbstractContent': 'src/tb/apps/content/models/AbstractContent',
        'content.models.Content': 'src/tb/apps/content/models/Content',
        'content.models.ContentSet': 'src/tb/apps/content/models/ContentSet',
        'content.models.ContentRevision': 'src/tb/apps/content/models/ContentRevision',

        //Templates
        'content/tpl/button': 'src/tb/apps/content/templates/button.twig',
        'content/tpl/content_breadcrumb': 'src/tb/apps/content/templates/content-breadcrumb.twig',
        'content/tpl/contribution/index': 'src/tb/apps/content/templates/contribution.index.twig',
        'content/tpl/edit/contribution/index': 'src/tb/apps/content/templates/edit-contribution.index.twig',
        'content/tpl/carousel_blocks': 'src/tb/apps/content/templates/carousel-blocks.twig',
        'content/tpl/block_display': 'src/tb/apps/content/templates/block-display.twig',
        'content/tpl/dropzone': 'src/tb/apps/content/templates/dropzone.twig',
        'content/tpl/content-action': 'src/tb/apps/content/templates/content-action.twig',
        'content/tpl/breadcrumb': 'src/tb/apps/content/templates/breadcrumb.twig',
        'content/tpl/scrollzone': 'src/tb/apps/content/templates/scrollzone.twig',
        'content/tpl/block_description': 'src/tb/apps/content/templates/block-description.twig',

        //Views
        'content.view.contribution.index': 'src/tb/apps/content/views/content.view.contribution.index',
        'content.view.edit.contribution.index': 'src/tb/apps/content/views/content.view.edit_contribution.index'
    }
});

define("app.content", ['Core', 'content.pluginmanager'], function (Core) {

    'use strict';

    Core.ApplicationManager.registerApplication('content', {
        onInit: function () {
            Core.ApplicationManager.launchApplication('contribution');
        }
    });
});

