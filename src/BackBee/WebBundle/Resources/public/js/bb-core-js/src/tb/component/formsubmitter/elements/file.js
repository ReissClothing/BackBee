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

        var file = {

            services: {},

            compute: function (key, value, formObject) {
                var data = null,
                    form = jQuery('#' + formObject.id),
                    elementPath = form.find('span.' + key + '_path'),
                    elementSrc = form.find('span.' + key + '_src'),
                    elementOriginalName = form.find('span.' + key + '_originalname');

                if (value === 'updated') {
                    data = {};
                    data.path = elementPath.text();
                    data.originalname = elementOriginalName.text();

                    if (elementSrc.text().length > 0) {
                        data.src = elementSrc.text();
                    }
                }

                return data;
            }
        };

        return file;
    }
);