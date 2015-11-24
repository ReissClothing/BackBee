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
define(['component!formbuilder', 'component!translator'], function (formbuilder, translator) {
    'use strict';

    var configure = function (view) {

        return {
            elements: {
                firstname: {
                    type: 'text',
                    label: translator.translate('first_name'),
                    value: view.user.getObject().firstname
                },
                lastname: {
                    type: 'text',
                    label: translator.translate('last_name'),
                    value: view.user.getObject().lastname
                },
                email: {
                    type: 'text',
                    label: translator.translate('email'),
                    value: view.user.getObject().email
                },
                username: {
                    type: 'text',
                    label: translator.translate('username'),
                    value: view.user.getObject().username
                },
                activated: {
                    type: 'checkbox',
                    options: {
                        activated: translator.translate('account_activated')
                    },
                    checked: 'activated'
                },
                api_key_enabled: {
                    type: 'checkbox',
                    options: {
                        api_key_enabled: translator.translate('api_key_enabled')
                    },
                    checked: 'api_key_enabled'
                }
            },

            onSubmit: function (data) {
                data.activated = (data.activated === 'activated');
                data.api_key_enabled = (data.api_key_enabled === 'api_key_enabled');
                view.user.populate(data);
                view.user.getObject().generate_password = true;
                view.dfd.resolve(view.user);
            },

            onValidate: function (form, data) {
                if (!data.hasOwnProperty('username') || data.username.trim().length === 0) {
                    form.addError('username', translator.translate('username_is_required'));
                }
                if (!data.hasOwnProperty('username') || data.username.trim().length < 6) {
                    form.addError('username', translator.translate('username_should_contain_6_characters'));
                }
                if (!data.hasOwnProperty('email') || data.email.trim().length === 0) {
                    form.addError('email', translator.translate('email_is_required'));
                } else {
                    if (!/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,6}$/i.test(data.email.trim())) {
                        form.addError('email', translator.translate('email is invalid'));
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
