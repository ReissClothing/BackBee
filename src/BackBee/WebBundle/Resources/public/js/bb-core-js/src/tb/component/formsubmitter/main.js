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

require.config({
    paths: {
        //Elements
        'fs.elements': 'src/tb/component/formsubmitter/elements',

        'CfElementInterpreter': 'src/tb/component/formsubmitter/ElementInterpreter'
    }
});

define(
    [
        'require',
        'jquery',
        'Core',
        'Core/Utils',
        'jsclass'
    ],
    function (require, jQuery) {

        'use strict';

        var Utils = require('Core/Utils'),

            FormSubmitter = new JS.Class({

                process: function (data, form) {
                    var dfd = jQuery.Deferred();

                    this.computeData(data, form).done(function () {
                        var key,
                            element,
                            submitter,
                            result = {};

                        for (key in data) {
                            if (data.hasOwnProperty(key)) {
                                element = data[key];
                                submitter = require('CfElementInterpreter!' + form.elements[key].type);
                                result[key] = submitter.compute(key, element, form);
                            }
                        }

                        dfd.resolve(result);
                    });

                    return dfd.promise();
                },

                computeData: function (data, form) {
                    var key,
                        promises = [];

                    for (key in data) {
                        if (data.hasOwnProperty(key)) {
                            promises.push(Utils.requireWithPromise(['CfElementInterpreter!' + form.elements[key].type]));
                        }
                    }

                    return jQuery.when.apply(undefined, promises).promise();
                }
            });

        return new JS.Singleton(FormSubmitter);
    }
);