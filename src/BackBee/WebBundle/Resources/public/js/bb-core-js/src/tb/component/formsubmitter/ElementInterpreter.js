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

(function () {

    'use strict';

    define(['Core', 'jquery'], function (Core, jQuery) {


        var textSubmitter = [
                'text',
                'hidden',
                'password',
                'textarea'
            ],
            selectSubmitter = [
                'select',
                'checkbox',
                'radio'
            ];

        return {
            load: function (name, req, onload) {
                var self = this;

                if (textSubmitter.indexOf(name) !== -1) {
                    name = 'text';
                } else if (selectSubmitter.indexOf(name) !== -1) {
                    name = 'select';
                }

                req(['fs.elements/' + name], function (elementObject) {
                    self.getServices(elementObject.services).done(function () {

                        var key,
                            i = 0;

                        for (key in elementObject.services) {
                            if (elementObject.services.hasOwnProperty(key)) {
                                elementObject[key] = arguments[i];
                                i = i + 1;
                            }
                        }

                        onload(elementObject);
                    });
                }, function () {
                    onload(null);
                });
            },

            getServices: function (services) {
                var key,
                    promises = [];

                for (key in services) {
                    if (services.hasOwnProperty(key)) {

                        promises.push(Core.ApplicationManager.invokeService(services[key]));
                    }
                }

                return jQuery.when.apply(undefined, promises).promise();
            }
        };
    });
}());