/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */
define(['component!formbuilder'], function (formbuilder) {
    'use strict';

    var configure = function (view) {

        return {
            elements: {
                old_password: {
                    type: 'password',
                    label: 'Password'
                },
                password: {
                    type: 'password',
                    label: 'New password'
                },
                confirm_password: {
                    type: 'password',
                    label: 'Confirm password'
                }
            },

            onSubmit: function (data) {
                view.dfd.resolve(data);
            },

            onValidate: function (form, data) {
                if (!data.hasOwnProperty('old_password') || data.old_password.trim().length === 0) {
                    form.addError('old_password', 'password is required');
                }

                if (!data.hasOwnProperty('confirm_password') || data.confirm_password.trim().length === 0) {
                    form.addError('confirm_password', 'Confirm password is required');
                }

                if (!data.hasOwnProperty('password') || data.password.trim().length === 0) {
                    form.addError('password', 'New password is required');
                } else {
                    if (data.password !== data.confirm_password) {
                        form.addError('password', 'New password and Confirm password are different');
                    }
                }
            }
        };
    };

    return {
        construct: function (view, errors) {
            var config = configure(view),
                form,
                key;

            if (undefined !== errors) {
                for (key in errors) {
                    if (errors.hasOwnProperty(key) &&
                            config.elements.hasOwnProperty(key)) {
                        config.elements[key].error = errors[key];
                    }
                }
            }

            form = formbuilder.renderForm(config);

            form.done(function (tpl) {
                view.popin.setContent(tpl);
            });
        }
    };
});