
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

        var File = {

            services: {
                'ContentManager': 'content.main.getContentManager'
            },


            init: function (definition) {
                this.definition = definition;

                return this;
            },

            getConfig: function (object, parent) {
                var dfd = jQuery.Deferred(),
                    config = {
                        'type': 'file',
                        'label': this.definition.type,
                        'dropzone': {
                            'default_thumbnail': parent.data.image
                        }
                    },
                    extra = parent.definition.defaultOptions[object.name].extra;

                if (extra) {
                    if (extra.dropzone) {
                        jQuery.extend(config.dropzone, extra.dropzone);
                    }
                }

                if (object !== undefined) {
                    this.populateConfig(object, config, dfd);
                } else {
                    dfd.resolve(config);
                }

                return dfd.promise();
            },

            populateConfig: function (object, config, dfd) {

                if (undefined === object.uid || undefined === object.type) {
                    dfd.reject('null_content');
                    return;
                }

                var element = this.ContentManager.buildElement({'uid': object.uid, 'type': object.type});

                element.getData('elements', true, true).done(function (elements) {
                    config.label = object.name;
                    config.object_name = object.name;
                    config.element = element;

                    if (elements.path && elements.path !== '' && elements.originalname && elements.originalname !== '') {
                        config.value = {
                            thumbnail: config.dropzone.default_thumbnail,
                            name: elements.originalname,
                            path: elements.path
                        };
                    }

                    dfd.resolve(config);
                }).fail(function (data, response) {
                    dfd.reject(data, response);
                });
            }
        };

        return File;
    }
);
