/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee.
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
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */
define(
    [
        'Core',
        'Core/Renderer',
        'user/entity/user',
        'component!notify',
        'require',
        'Core/Utils',
        'jquery'
    ],
    function (Core, renderer, User, Notify, require, Utils, jQuery) {
        'use strict';
        var trans = Core.get('trans') || function (value) {return value; };

        Core.ControllerManager.registerController('UserController', {

            appName: 'user',

            pagination_params: null,

            config: {
                imports: ['user/repository/user.repository'],
                define: {
                    indexService: ['user/repository/user.repository', 'user/views/user/view.list', 'text!user/templates/user/list.twig'],
                    newService: ['user/views/user/form.view', 'user/form/new.user.form'],
                    editService:  ['user/views/user/form.view', 'user/form/edit.user.form'],
                    deleteService: ['user/views/user/delete.view'],
                    showCurrentService: ['user/repository/user.repository', 'user/views/user/toolbar', 'bootstrap-dropdown'],
                    editCurrentService:  ['user/views/user/current.form.view', 'user/form/current.user.form'],
                    changePasswordService:  ['user/views/user/current.form.view', 'user/form/password.user.form'],
                    logoutService: ['component!session', 'Core/DriverHandler', 'Core/RestDriver']
                }
            },

            onInit: function (req) {
                this.repository = req('user/repository/user.repository');
            },

            parseRestError: function (error) {
                if (error) {
                    error = JSON.parse(error);
                    return error.errors;
                }
            },

            updateIndexView: function (req, popin, users, current) {
                var View = req('user/views/user/view.list'),
                    template = req('text!user/templates/user/list.twig'),
                    i;

                users = Utils.castAsArray(users);

                for (i = users.length - 1; i >= 0; i = i - 1) {
                    users[i] = new View({user: users[i], current: current});
                }

                popin.addUsers(renderer.render(template, {users: users}));
            },

            /**
             * Index action
             * Show the index in the edit contribution toolbar
             */
            indexService: function (req, popin, params) {
                var current = Core.get('current_user'),
                    self = this;


                if (params !== undefined) {
                    if (params.reset === true) {
                        this.pagination_params = null;
                    } else {
                        this.pagination_params = params;
                    }
                }

                this.repository.paginate(this.pagination_params).then(
                    function (users) {
                        if (current === undefined) {
                            self.repository.find('current').then(
                                function (user_values) {
                                    current = new User();
                                    current.populate(user_values);

                                    self.updateIndexView(req, popin, users, current);
                                }
                            );
                        } else {
                            self.updateIndexView(req, popin, users, current);
                        }
                    },
                    function () {
                        popin.addUsers('');
                        Core.exception.silent('UserControllerEception', 500, 'User REST paginate call fail');
                    }
                );
            },

            initFormView: function (user, popin, View, action, error, id) {
                var self = this,
                    view = new View({popin: popin, user: user, errors: error}, action, id),
                    user_id = user.id();

                view.display().then(function (user) {

                    self.repository.save(user.getObject()).then(
                        function () {
                            popin.popinManager.destroy(view.popin);
                            self.indexService(require, popin);
                            Notify.success(trans('user_save_success'));
                            if ('new' === action) {
                                setTimeout(Notify.warning, 1500, trans('to_assign_a_user_rights_remember_to_drag_and_drop_the_user_into_a_group'));
                            }
                        },
                        function (error) {
                            var errors = self.parseRestError(error),
                                internalError = '';

                            if (undefined !== errors) {
                                Notify.error(trans('user_save_fail'));

                                if (undefined !== user_id) {
                                    user.populate({id: user_id});
                                }

                                popin.popinManager.destroy(view.popin);
                                self.initFormView(user, popin, View, action, errors, id);
                            } else {
                                popin.popinManager.destroy(view.popin);
                                self.indexService(require, popin);

                                error = JSON.parse(error);
                                if (error.internal_error) {
                                    internalError = ' ' + error.internal_error;
                                }

                                Notify.error(trans('server_error') + internalError);
                            }
                        }
                    );
                });
            },

            newService: function (req, popin) {
                if (document.getElementById('new-user-subpopin') === null) {
                    this.initFormView(new User(), popin, req('user/views/user/form.view'), 'new', null, 'new-user-subpopin');
                } else {
                    jQuery('#new-user-subpopin').dialog('open');
                }
            },

            editService: function (req, popin, user_id) {
                if (document.getElementById('edit-user-subpopin') === null) {
                    var user = new User(),
                        self = this;

                    this.repository.find(user_id).done(function (user_values) {
                        user.populate(user_values);
                        self.initFormView(user, popin, req('user/views/user/form.view'), 'edit', null, 'edit-user-subpopin');
                    });
                } else {
                    jQuery('#edit-user-subpopin').dialog('open');
                }
            },

            deleteService: function (req, popin, user_id) {
                var user = new User(),
                    self = this,
                    View = req('user/views/user/delete.view'),
                    view;

                this.repository.find(user_id).done(function (user_values) {
                    user.populate(user_values);

                    view = new View({popin: popin, user: user});
                    view.display().then(
                        function () {
                            self.repository.delete(user_id).done(function () {
                                self.indexService(require, popin);
                                Core.ApplicationManager.invokeService('user.group.index', popin);
                                Notify.success(trans('User') + ' ' + user.login() + ' ' + trans('has_been_deleted'));
                            });
                            view.destruct();
                        },
                        function () {
                            view.destruct();
                        }
                    );
                });
            },

            addGroupService: function (popin, user_id, group_id) {
                var user = new User(),
                    self = this;

                self.repository.find(user_id).then(
                    function (user_values) {
                        var already_grouped = false;

                        user_values.groups.forEach(function (group) {
                            if (parseInt(group_id, 10) === group.id) {
                                already_grouped = true;
                            }
                        });

                        if (!already_grouped) {
                            user_values.groups[group_id] = 'added';

                            user.populate({
                                id: user_id,
                                groups: user_values.groups
                            });

                            self.repository.save(user.getObject()).then(
                                function () {
                                    Core.ApplicationManager.invokeService('user.group.index', popin);
                                    Core.ApplicationManager.invokeService('user.user.index', popin);
                                    Notify.success(trans('user_update_success'));
                                },
                                function () {
                                    Notify.error(trans('user_update_fail'));
                                }
                            );
                        } else {
                            Notify.warning(trans('user_is_already_in_this_group'));
                        }
                    },
                    function () {
                        self.indexService(require, popin);
                        Notify.error(trans('user_not_found'));
                    }
                );
            },

            removeGroupService: function (popin, user_id, group_id) {
                var user = new User(),
                    self = this;

                self.repository.find(user_id).then(
                    function (user_values) {
                        var already_grouped = false;

                        user_values.groups.forEach(function (group) {
                            if (parseInt(group_id, 10) === group.id) {
                                already_grouped = true;
                            }
                        });

                        if (already_grouped) {
                            user_values.groups[group_id] = 'removed';

                            user.populate({
                                id: user_id,
                                groups: user_values.groups
                            });

                            self.repository.save(user.getObject()).then(
                                function () {
                                    Core.ApplicationManager.invokeService('user.group.index', popin);
                                    Core.ApplicationManager.invokeService('user.user.index', popin);
                                    Notify.success(trans('user_update_success'));
                                },
                                function () {
                                    Notify.error(trans('user_update_fail'));
                                }
                            );
                        } else {
                            Notify.warning(trans('user_is_not_in_this_group'));
                        }
                    },
                    function () {
                        self.indexService(require, popin);
                        Notify.error(trans('user_not_found'));
                    }
                );
            },

            showCurrentService: function (req) {
                var View = req('user/views/user/toolbar'),
                    view;
                this.repository.find('current').then(
                    function (user_values) {
                        var user = new User();
                        user.populate(user_values);
                        Core.set('current_user', user);

                        view = new View({el: jQuery('#bb5-navbar-secondary > div'), user: user_values});
                        view.render();
                    },
                    function () {
                        Notify.error('error_retry_later');
                    }
                );
            },

            changePasswordService: function (req, user, error) {
                var self = this,
                    View = req('user/views/user/current.form.view'),
                    view = new View({user: user, errors: error}, 'password');

                view.display().then(function (patch) {
                    patch.id = user.id();

                    self.repository.save(patch).then(
                        function () {
                            view.destroy();
                            Notify.success('password_updated');
                        },
                        function (error) {
                            view.destroy();
                            self.editCurrentService(user, self.parseRestError(error));
                        }
                    );
                });
            },

            editCurrentService: function (req, user, error) {
                var self = this,
                    View = req('user/views/user/current.form.view'),
                    view = new View({user: user, errors: error}, 'current');

                view.display().then(function (user) {
                    var patch = {
                        id: user.id(),
                        firstname: user.firstname(),
                        lastname: user.lastname(),
                        email: user.email()
                    };

                    self.repository.save(patch).then(
                        function () {
                            view.destroy();
                            Notify.success('account_updated');
                        },
                        function (error) {
                            Notify.error('error_retry_later');

                            view.destroy();
                            self.editCurrentService(user, self.parseRestError(error));
                        }
                    );
                });
            },

            updateStatusService: function (popin, user) {
                var self = this;

                self.repository.save(user).then(
                    function () {
                        Notify.success(trans('user_update_success'));
                    },
                    function () {
                        Notify.error(trans('user_update_fail'));
                        self.indexService(require, popin);
                    }
                );
            },

            logoutService: function (req) {
                var DriverHandler = req('Core/DriverHandler');
                DriverHandler.addDriver('rest', req('Core/RestDriver'));
                DriverHandler.delete('security/session').then(
                    function () {
                        req('component!session').destroy();
                        document.cookie = 'PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';
                        document.location.reload();
                    }
                );
            }
        });
    }
);
