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
        'Core/DriverHandler',
        'Core/RestDriver',
        'Core',
        'URIjs/URI',
        'jsclass'
    ],
    function (CoreDriverHandler, CoreRestDriver, Core, URI) {

        'use strict';

        var putMandatoriesAttribute = ['title', 'alttitle', 'url', 'target', 'state', 'redirect', 'layout_uid'],

            /**
             * Page repository class
             * @type {Object} JS.Class
             */
            PageRepository = new JS.Class({

                TYPE: 'page',

                /**
                 * Initialize of Page repository
                 */
                initialize: function () {
                    CoreDriverHandler.addDriver('rest', CoreRestDriver);
                },

                /**
                 * Verify if the method is put method with a mandatories attributes array
                 * @param {Object} data
                 * @returns {Boolean}
                 */
                isPutMethod: function (data) {
                    var key,
                        mandatory,
                        isValid = true;

                    for (key in putMandatoriesAttribute) {
                        if (putMandatoriesAttribute.hasOwnProperty(key)) {
                            mandatory = putMandatoriesAttribute[key];
                            if (!data.hasOwnProperty(mandatory)) {
                                isValid = false;
                                break;
                            }
                        }
                    }

                    return isValid;
                },

                /**
                 * Find the current page
                 * @returns {Promise}
                 */
                findCurrentPage: function () {
                    return CoreDriverHandler.read(this.TYPE, {'uid': Core.get('page.uid')});
                },


                findAncestors: function (page_uid) {

                    Core.Mediator.subscribeOnce('rest:send:before', function (request) {
                        var url = new URI(request.url);
                        url.segment('ancestors');
                        request.url = url.normalize().toString();
                    });

                    return CoreDriverHandler.read(this.TYPE, {'uid': page_uid});
                },

                setMetadata: function (page_uid, data) {
                    Core.Mediator.subscribeOnce('rest:send:before', function (request) {
                        var url = new URI(request.url);

                        url.segment('metadata');

                        request.url = url.normalize().toString();
                    });

                    return CoreDriverHandler.update(this.TYPE, data, {'id': page_uid});
                },

                /**
                 * Return the metadata of page
                 * @param {String} page_uid
                 * @returns {Promise}
                 */
                getMetadata: function (page_uid) {
                    Core.Mediator.subscribeOnce('rest:send:before', function (request) {
                        var url = new URI(request.url);

                        url.segment('metadata');

                        request.url = url.normalize().toString();
                    });

                    return CoreDriverHandler.read(this.TYPE, {'id': page_uid}, {}, 0, null);
                },

                /**
                 * Get the page by uid
                 * @param {String} uid
                 */
                find: function (uid) {
                    return CoreDriverHandler.read(this.TYPE, {'id': uid});
                },

                /**
                 * Find pages with children
                 * @param {String} uid
                 */
                findChildren: function (parent_uid, start, limit, only_section) {
                    var filters = {'state': [0, 1, 2, 3], 'parent_uid': parent_uid, 'level_offset': 1};
                    if (only_section) {
                        filters.has_children = '';
                    }
                    return this.search(filters, start, limit, {'leftnode': 'asc'});
                },

                findContents: function (contentType, contentUid) {
                    return CoreDriverHandler.read(this.TYPE, {content_type: contentType, content_uid: contentUid});
                },

                findRoot: function (site_uid) {
                    var params = {root: 1};
                    if (typeof site_uid === "string" && site_uid.length !== 0) {
                        params.site_uid = site_uid;
                    }
                    return CoreDriverHandler.read(this.TYPE, params);
                },

                /**
                 * Search pages
                 * @param array filters
                 * @param {int} start
                 * @param {int} count
                 * @param {Function} callback
                 */
                search: function (filters, start, count, orderBy) {
                    start = start || 0;
                    count = count || 25;
                    orderBy = orderBy || {};

                    return CoreDriverHandler.read(this.TYPE, filters, orderBy, start, count);
                },

                /**
                 * Save the page with a correctly method
                 * @param {Object} data
                 * @returns {Promise}
                 */
                save: function (data) {
                    var result,
                        uid;

                    if (data.hasOwnProperty('uid')) {
                        uid = data.uid;

                        delete data.uid;

                        if (this.isPutMethod(data)) {
                            result = CoreDriverHandler.update(this.TYPE, data, {'id': uid}, {}, 0, null);
                        } else {
                            result = CoreDriverHandler.patch(this.TYPE, data, {'id': uid});
                        }
                    } else {
                        result = CoreDriverHandler.create(this.TYPE, data);
                    }
                    result.done(function () {
                        Core.ApplicationManager.invokeService('page.main.updatePageInfo');
                    });

                    return result;
                },

                /**
                 * Delete the page
                 * @param {String} uid
                 * @returns {Promise}
                 */
                "delete": function (uid) {
                    return CoreDriverHandler["delete"](this.TYPE, {'id': uid}, {}, 0, null);
                },

                /**
                 * Clone the page
                 * @param {String} uid
                 * @param {Object} data
                 * @returns {Promise}
                 */
                clone: function (uid, data) {
                    Core.Mediator.subscribeOnce('rest:send:before', function (request) {
                        var url = new URI(request.url);

                        url.segment(uid);
                        url.segment('clone');

                        request.url = url.normalize().toString();
                    });

                    return CoreDriverHandler.create(this.TYPE, data);
                },

                /**
                 * Move node
                 * @param {String} page_uid
                 * @param {String} parent_uid
                 * @param {String} next_uid
                 */
                moveNode: function (page_uid, data) {
                    return CoreDriverHandler.patch(this.TYPE, data, {'id': page_uid});
                },

                /**
                 * Get workflow of page
                 * @param {String} uid
                 * @todo move to layout application
                 * @returns {Promise}
                 */
                getWorkflowState: function (layout_uid) {
                    Core.Mediator.subscribeOnce('rest:send:before', function (request) {
                        var url = new URI(request.url);

                        url.segment('workflow_state');

                        request.url = url.normalize().toString();
                    });
                    return CoreDriverHandler.read('layout', {'id': layout_uid});
                },

                groupedPatch: function (uids, data) {
                    var i,
                        key,
                        values = [];
                    for (i = 0; i < uids.length; i = i + 1) {
                        values[i] = {};
                        values[i].uid = uids[i];
                        for (key in data) {
                            if (data.hasOwnProperty(key)) {
                                values[i][key] = data[key];
                            }
                        }
                    }

                    return CoreDriverHandler.update(this.TYPE, values);
                }
            });

        return new JS.Singleton(PageRepository);
    }
);
