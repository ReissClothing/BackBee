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
        'jquery',
        'jsclass'
    ],
    function (jQuery) {

        'use strict';

        var ContentSet = {

            services: {
                'ContentManager': 'content.main.getContentManager',
                'DefinitionManager': 'content.main.getDefinitionManager'
            },

            init: function (definition) {
                this.definition = definition;

                return this;
            },

            getConfig: function (object) {
                var self = this,
                    dfd = jQuery.Deferred(),
                    config,
                    element = this.ContentManager.buildElement({'uid': object.uid, 'type': object.type});

                element.getData().done(function () {
                    self.getChildren(element).done(function () {
                        var i,
                            children = [];

                        for (i in arguments) {
                            if (arguments.hasOwnProperty(i)) {
                                children.push(arguments[i]);
                            }
                        }

                        config = {
                            'type': 'contentSet',
                            'label': object.name,
                            'object_name': object.name,
                            'object_uid': object.uid,
                            'object_type': object.type,
                            'children': children,
                            'element': element
                        };

                        dfd.resolve(config);
                    });
                }).fail(function (data, response) {
                    dfd.reject(data, response);
                });

                return dfd.promise();
            },

            getChildren: function (content) {
                var key,
                    element,
                    elements = content.data.elements,
                    promises = [];

                for (key in elements) {
                    if (elements.hasOwnProperty(key)) {
                        element = elements[key];
                        promises.push(this.loadChild(element.uid, element.type));
                    }
                }

                return jQuery.when.apply(undefined, promises).promise();
            },

            loadChild: function (uid, type) {
                var dfd = jQuery.Deferred(),
                    subcontent = this.ContentManager.buildElement({'uid': uid, 'type': type});

                subcontent.getData().done(function () {
                    dfd.resolve(subcontent);
                });

                return dfd.promise();
            }
        };

        return ContentSet;
    }
);