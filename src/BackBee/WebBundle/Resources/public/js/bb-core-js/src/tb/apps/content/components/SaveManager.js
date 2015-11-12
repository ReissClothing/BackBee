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
        'content.container',
        'content.repository',
        'jquery',
        'jsclass'
    ],
    function (ContentContainer,
              ContentRepository,
              jQuery
            ) {

        'use strict';

        var SaveManager = new JS.Class({

            /**
             * Save all content updated
             */
            save: function () {
                var contents = this.getContentsToSave(),
                    promises = [],
                    content,
                    key;

                for (key in contents) {
                    if (contents.hasOwnProperty(key)) {
                        content = contents[key];
                        this.commit(content);
                        promises.push(this.push(content));
                    }
                }

                return jQuery.when.apply(undefined, promises).promise();
            },

            getContentsToSave: function () {
                return ContentContainer.getContentsUpdated();
            },

            /**
             * Update elements and parameters
             * @param {Object} content
             */
            commit: function (content) {
                content.updateRevision();
            },

            /**
             * Merge revision into content
             * @param {Object} content
             */
            merge: function (content) {
                var revision = content.revision;

                if (false === revision.isEmpty()) {
                    this.mergeParameters(content);
                    this.mergeElements(content);
                }

                content.setUpdated(false);
            },

            /**
             * Save in database a content and merge diff from revision
             * @param {Object} content
             */
            push: function (content) {
                var self = this,
                    dfd = jQuery.Deferred();

                if (content.isSavable()) {
                    ContentRepository.save(content.revision).done(function () {
                        self.merge(content);
                        dfd.resolve();
                    });
                }

                return dfd.promise();
            },

            mergeElements: function (content) {
                var revision = content.revision,
                    elements = revision.elements,
                    isMergeable = false,
                    key;

                if (content.data !== undefined) {
                    if (content.data.elements !== undefined) {
                        isMergeable = true;
                    }
                }

                if (undefined !== elements && isMergeable === true) {
                    if (content.isAContentSet()) {
                        content.data.elements = elements;
                    } else {
                        for (key in elements) {
                            if (elements.hasOwnProperty(key)) {
                                if (content.data.elements.hasOwnProperty(key)) {
                                    content.data.elements[key] = elements[key];
                                }
                            }
                        }
                    }
                }

                delete revision.elements;
            },

            /**
             * Merge parameters to content and delete in revision
             * @param {Content} content
             */
            mergeParameters: function (content) {
                var revision = content.revision,
                    parameters = revision.parameters,
                    key;

                if (undefined !== parameters) {
                    for (key in parameters) {
                        if (parameters.hasOwnProperty(key)) {
                            if (content.data.parameters.hasOwnProperty(key)) {
                                content.data.parameters[key].value = parameters[key];
                            }
                        }
                    }
                    delete revision.parameters;
                }
            }
        });

        return new JS.Singleton(SaveManager);
    }
);