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

        var mediaSelector = {

            services: {},

            compute: function (key, value, formObject) {
                var data = null,
                    form = jQuery('#' + formObject.id),
                    element = form.find('.element_' + key),
                    list = element.find('ul.media_list li');

                if (value === 'updated') {
                    data = [];
                    list.each(function () {
                        var li = jQuery(this),
                            object = {
                                'type': li.data('type'),
                                'uid': li.data('uid'),
                                'title': li.data('title'),
                                'folder_uid': li.data('folder'),
                                'media_id': li.data('media'),
                                'image': li.data('image')
                            };

                        data.push(object);
                    });
                }

                return data;
            }
        };

        return mediaSelector;
    }
);