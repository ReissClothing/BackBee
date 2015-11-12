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
    ['require', 'Core/Renderer', 'jquery', 'component!popin', 'text!bundle/templates/confirm.twig'],
    function (require, Renderer, jQuery, PopinManager) {
        'use strict';

        var popin;

        /**
         * View of new page
         * @type {Object} Backbone.View
         */
        return Backbone.View.extend({

            popin_config: {
                id: 'bundle-cofirm-popin',
                width: 250,
                top: 180,
                close: function () {
                    PopinManager.destroy(popin);
                }
            },

            bindAction: function () {
                var self = this;
                jQuery('#bb-bundle-validate').click(function () {
                    self.dfd.resolve();
                });
                jQuery('#bb-bundle-cancel').click(function () {
                    self.dfd.reject();
                });

            },

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data) {
                var text = 'do_you_want_' + data.action + '_this_bundle',
                    tpl = Renderer.render(require('text!bundle/templates/confirm.twig'), {text: text});
                popin = PopinManager.createPopIn(this.popin_config);

                popin.setContent(tpl);
            },


            display: function () {
                this.dfd = jQuery.Deferred();

                popin.display();
                this.bindAction();
                return this.dfd.promise();
            },

            destruct: function () {
                PopinManager.destroy(popin);
            }
        });
    }
);
