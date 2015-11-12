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

        var Keyword = {

            services: {
                'ContentManager': 'content.main.getContentManager',
                'DefinitionManager': 'content.main.getDefinitionManager',
                'ContentRepository': 'content.main.getRepository',
                'KeywordRepository': 'content.main.getKeywordRepository'
            },

            init: function (definition) {
                this.definition = definition;

                return this;
            },

            getConfig: function (object, content) {
                var self = this,
                    dfd = jQuery.Deferred(),
                    config;

                this.buildElements(object).done(function () {
                    self.getKeywords(arguments).done(function () {
                        config = {
                            'type': 'keywordSelector',
                            'label': object.name,
                            'object_name': object.name,
                            'value': self.getValue(arguments)
                        };

                        content[object.name + '_values'] = config.value;

                        dfd.resolve(config);
                    });
                });

                return dfd.promise();
            },

            getValue: function (elements) {
                var key,
                    element,
                    value = [];

                for (key in elements) {
                    if (elements.hasOwnProperty(key)) {
                        element = elements[key];
                        if (element !== null) {
                            value.push({
                                'keyword': element.keyword,
                                'uid': element.uid,
                                'object_uid': element.object_uid
                            });
                        }
                    }
                }

                return value;
            },

            getKeywords: function (elements) {
                var promises = [],
                    key,
                    element;

                for (key in elements) {
                    if (elements.hasOwnProperty(key)) {
                        element = elements[key];
                        if (element !== null) {
                            promises.push(this.findValue(element));
                        }
                    }
                }

                return jQuery.when.apply(undefined, promises).promise();
            },

            findValue: function (element) {
                var dfd = jQuery.Deferred(),
                    uid = element.elements.value,
                    objectUid = element.object_uid;

                if (uid !== undefined && uid !== '') {
                    this.KeywordRepository.find(uid).done(function (element) {
                        element.object_uid = objectUid;
                        dfd.resolve(element);
                    });
                } else {
                    dfd.resolve(null);
                }

                return dfd.promise();
            },

            buildElements: function (object) {
                var promises = [],
                    element,
                    key;

                if (!jQuery.isArray(object.elements)) {
                    if (undefined === object.uid) {
                        promises.push(null);
                    } else {
                        promises.push(this.buildElement({'uid': object.uid, 'type': object.type}));
                    }
                } else {
                    for (key in object.elements) {
                        if (object.elements.hasOwnProperty(key)) {
                            element = object.elements[key];
                            promises.push(this.buildElement({'uid': element.uid, 'type': element.type}));
                        }
                    }
                }

                return jQuery.when.apply(undefined, promises).promise();
            },

            buildElement: function (object) {
                var dfd = jQuery.Deferred();

                this.ContentRepository.findData(object.type, object.uid).done(function (data) {
                    data.object_uid = object.uid;
                    dfd.resolve(data);
                });

                return dfd.promise();
            }
        };

        return Keyword;
    }
);