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
define(
    ['require', 'Core', 'Core/Renderer', 'jquery', 'text!user/templates/user/delete.twig'],
    function (require, Core, Renderer, jQuery) {
        'use strict';

        var trans = Core.get('trans') || function (value) {return value; },
            mainPopin,
            popin;

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            popin_config: {
                id: 'delete-user-subpopin',
                width: 250,
                top: 180,
                close: function () {
                    mainPopin.popinManager.destroy(popin);
                }
            },

            bindAction: function () {
                var self = this;
                jQuery('#bb-user-validate').click(function () {
                    self.dfd.resolve();
                });
                jQuery('#bb-user-cancel').click(function () {
                    self.dfd.reject();
                });

            },

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data) {
                mainPopin = data.popin;
                this.user = data.user;
                popin = mainPopin.popinManager.createSubPopIn(mainPopin.popin, this.popin_config);
                popin.setTitle(trans('delete_user'));
                this.tpl = Renderer.render(require('text!user/templates/user/delete.twig'), {user: this.user});
                popin.setContent(this.tpl);
            },


            display: function () {
                this.dfd = jQuery.Deferred();
                popin.display();
                this.bindAction();
                return this.dfd.promise();
            },

            destruct: function () {
                mainPopin.popinManager.destroy(popin);
            }
        });
    }
);
