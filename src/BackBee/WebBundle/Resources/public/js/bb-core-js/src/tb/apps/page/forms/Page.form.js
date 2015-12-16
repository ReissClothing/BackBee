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

define(['page.abstract.form', 'jquery', 'jsclass'], function (Form, jQuery) {
    'use strict';

    var PageForm = new JS.Class(Form, {

        new: function (moveTo) {

            this.clear();

            var dfd = jQuery.Deferred(),
                config = {
                    elements: {
                        title: this.form.title,
                        alttitle: this.form.alttitle,
                        url: this.form.url,
                        target: this.form.target,
                        redirect: this.form.redirect,
                        layout_uid: this.form.layout_uid
                    },
                    form_name: 'new-page'
                };

            if (true === moveTo) {
                config.elements.move_to = this.form.move_to;
            }

            this.getLayoutsObject().done(function (layoutObject) {
                config.elements.layout_uid = layoutObject;
                dfd.resolve(config);
            });

            return dfd.promise();
        },

        edit: function (page_uid, moveTo) {
            var dfd = jQuery.Deferred(),
                config = {
                    elements: {
                        title: this.form.title,
                        alttitle: this.form.alttitle,
                        url: this.form.url,
                        target: this.form.target,
                        redirect: this.form.redirect,
                        layout_uid: this.form.layout_uid,
                        state: this.form.state
                    },
                    form_name: 'edit-page',
                    page_uid: page_uid
                },
                self = this;

            if (true === moveTo) {
                config.elements.move_to = this.form.move_to;
            }

            this.getPage(page_uid).done(function (page) {
                self.getLayoutsObject().done(function (layoutObject) {
                    config.elements.layout_uid = layoutObject;
                    self.map(page, config);
                    dfd.resolve(config);
                });
            });

            return dfd.promise();
        },

        clone: function (page_uid) {
            var dfd = jQuery.Deferred(),
                config = {
                    elements: {
                        title: this.form.title
                    }
                },
                self = this;

            this.getPage(page_uid).done(function (page) {
                self.map(page, config);
                dfd.resolve(config);
            });

            return dfd.promise();
        }

    });

    return new JS.Singleton(PageForm);
});
