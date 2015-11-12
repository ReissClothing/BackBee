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
        'jquery',
        'moment',
        'page.repository',
        'jsclass'
    ],
    function (Core, jQuery, Moment, PageRepository) {

        'use strict';

        var attributes = {
                'is_hidden': {
                    'key': 'is_hidden',
                    'label': 'Available in menus',
                    'treatment': function (value) {
                        return !value;
                    }
                },
                'state': {
                    'key': 'state_code',
                    'label': 'State',
                    'treatment': function (value) {
                        return (parseInt(value, 10) === 0) ? 'Offline' : 'Online';
                    }
                },
                'publishing': {
                    'key': 'publishing',
                    'label': 'Publishing date',
                    'treatment': function (value) {
                        return Moment.unix(value).format('YYYY/MM/DD HH:mm');
                    }
                },
                'archiving': {
                    'key': 'archiving',
                    'label': 'Archiving date',
                    'treatment': function (value) {
                        return Moment.unix(value).format('YYYY/MM/DD HH:mm');
                    }
                }
            },

            SaveManager = new JS.Class({

                data: {},

                /**
                 * Save all content updated
                 */
                save: function (data, pageUid) {
                    var self = this,
                        dfd = new jQuery.Deferred(),
                        key,
                        value,
                        dataToSave = {};

                    if (data.length > 0) {

                        dataToSave.uid = pageUid;
                        for (key in data) {
                            if (data.hasOwnProperty(key)) {
                                value = data[key];
                                if (this.data[value] !== undefined) {
                                    dataToSave[value] = this.data[value].value;
                                }
                            }
                        }

                        PageRepository.save(dataToSave).done(function () {
                            self.clear();
                            dfd.resolve();
                        });
                    } else {
                        dfd.resolve();
                    }

                    return dfd.promise();
                },

                addToSave: function (property, value) {
                    var attr = attributes[property],
                        object;

                    if (attr !== undefined) {

                        object = {
                            'label': attr.label,
                            'key': property,
                            'value': value,
                            'frontValue': (typeof attr.treatment === 'function') ? attr.treatment(value) : value,
                            'modified_at': new Moment().format('DD/MM/YYYY - HH:mm'),
                            'author': Core.get('current_user').fullName()
                        };

                        this.data[property] = object;
                    }
                },

                clear: function () {
                    this.data = {};
                },

                remove: function (key) {
                    if (this.data[key] !== undefined) {
                        delete this.data[key];
                    }
                },

                validateData: function (page) {
                    var key,
                        attr;

                    for (key in this.data) {
                        if (this.data.hasOwnProperty(key)) {

                            attr = attributes[key];
                            if (attr !== undefined) {
                                if (page[attr.key] === undefined) {
                                    delete this.data[key];
                                } else {
                                    if (page[attr.key] === this.data[key].value) {
                                        delete this.data[key];
                                    }
                                }
                            }
                        }
                    }

                    return this.data;
                }
            });

        return new JS.Singleton(SaveManager);
    }
);