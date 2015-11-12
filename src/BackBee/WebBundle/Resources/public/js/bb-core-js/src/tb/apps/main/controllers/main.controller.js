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

define(
    [
        'Core',
        'main.toolbar.manager',
        'jquery'
    ],
    function (Core, ToolbarManager, jQuery) {

        'use strict';

        Core.ControllerManager.registerController('MainController', {

            appName: 'main',

            config: {
                imports: []
            },

            onInit: function () {
                return;
            },

            indexAction: function () {
                return;
            },

            /**
             * Service for retrieve Toolbar manager
             */
            toolbarManagerService: function () {
                return ToolbarManager;
            },

            /**
             * Dispatch event `on:save:click``for all interested
             */
            saveService: function () {
                var eventName;

                if (true === Core.Scope.isOpen('block') || true === Core.Scope.isOpen('content')) {
                    eventName = 'on:content:save:click';
                } else if (true === Core.Scope.isOpen('page')) {
                    eventName = 'on:page:save:click';
                }

                Core.Mediator.publish(eventName);
            },

            /**
             * Dispatch event `on:validate:click``for all interested
             */
            validateService: function () {
                var eventName;

                if (true === Core.Scope.isOpen('block') || true === Core.Scope.isOpen('content')) {
                    eventName = 'on:content:validate:click';
                } else if (true === Core.Scope.isOpen('page')) {
                    eventName = 'on:page:validate:click';
                }

                Core.Mediator.publish(eventName);
            },

            /**
             * Dispatch event `on:cancel:click``for all interested
             */
            cancelService: function () {
                var eventName;

                if (true === Core.Scope.isOpen('block') || true === Core.Scope.isOpen('content')) {
                    eventName = 'on:content:cancel:click';
                } else if (true === Core.Scope.isOpen('page')) {
                    eventName = 'on:page:cancel:click';
                }

                Core.Mediator.publish(eventName);
            },

            setTitlePaneService: function (value) {
                jQuery('#toolbar-pane-name').text(value);
            }
        });
    }
);
