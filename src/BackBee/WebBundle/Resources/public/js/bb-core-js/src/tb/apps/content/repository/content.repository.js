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
        'Core/DriverHandler',
        'Core/RestDriver',
        'Core/RequestHandler',
        'Core/Request',
        'jquery'
    ],
    function (Core, CoreDriverHandler, CoreRestDriver, RequestHandler, Request, jQuery) {

        'use strict';

        var resourceName = 'classcontent',

            /**
             * Contnet repository class
             * @type {Object} JS.Class
             */
            ContentRepository = new JS.Class({

                TYPE: 'classcontent',

                /**
                 * Initialize of Page repository
                 */
                initialize: function () {
                    CoreDriverHandler.addDriver('rest', CoreRestDriver);
                },

                /**
                 * Find a content
                 * @param {String} type
                 * @param {String} uid
                 */
                find: function (type, uid) {
                    return CoreDriverHandler.read(this.TYPE + '/' + type, {'uid': uid});
                },

                /**
                 * Find all definitions
                 * @returns {Promise}
                 */
                findDefinitions: function () {
                    return CoreDriverHandler.read(this.TYPE, {'definition': ''});
                },

                findByUids: function (uids) {
                    uids = uids.join();

                    return CoreDriverHandler.read(this.TYPE, {'uids': uids, 'site_uid': ''});
                },

                /**
                 * Find all categories
                 * @returns {Promise}
                 */
                findCategories: function () {
                    var self = this,
                        dfd = jQuery.Deferred();

                    if (this.categories === undefined) {
                        CoreDriverHandler.read(this.TYPE + '-category').done(function (categories) {
                            self.categories = categories;
                            dfd.resolve(categories);
                        });
                    } else {
                        dfd.resolve(this.categories);
                    }

                    return dfd.promise();
                },

                /**
                 * Get all drafts
                 * @returns {Promise}
                 */
                getDrafts: function () {
                    return CoreDriverHandler.read(this.TYPE + '-draft');
                },

                /**
                 * find data of content
                 * @param {String} type
                 * @param {String} uid
                 * @returns {Promise}
                 */
                findData: function (type, uid) {
                    return CoreDriverHandler.read(this.TYPE + '/' + type, {'uid': uid, 'concise': ''});
                },

                /**
                 * Get the html of content
                 * @param {String} type
                 * @param {String} uid
                 * @param {String} renderMode
                 * @returns {Promise}
                 */
                getHtml: function (type, uid, renderMode) {
                    var request = new Request(),
                        url = '/rest/1/' + resourceName + '/' + type + '/' + uid,
                        currentPageUid = Core.get('page.uid'),
                        data = {};

                    request.addHeader('Accept', 'text/html');

                    if (undefined !== renderMode) {
                        data.mode = renderMode;
                    }

                    if (null !== currentPageUid) {
                        data.page_uid = currentPageUid;
                    }

                    request.setData(data);
                    request.setUrl(url);

                    return RequestHandler.send(request);
                },

                /**
                 * Save the content with a correctly method
                 * @param {Object} data
                 * @returns {Promise}
                 */
                save: function (data) {
                    var result;
                    if (data.hasOwnProperty('uid')) {
                        result = CoreDriverHandler.update(this.TYPE + '/' + data.type, data, {'id': data.uid});
                    } else {
                        result = CoreDriverHandler.create(this.TYPE + '/' + data.type);
                    }

                    return result;
                }
            });

        return new JS.Singleton(ContentRepository);
    }
);
