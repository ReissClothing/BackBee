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
        'Core',
        'Core/Renderer',
        'component!popin',
        'page.repository',
        'text!page/widgets/tpl/information_page',
        'component!translator',
        'jquery',
        'moment',
        'jsclass'
    ],

    function (Core, Renderer, PopinManager, PageRepository, template, translator, jQuery, moment) {

        'use strict';

        var InformationPageWidget = new JS.Class({

            /**
             * Init of popin
             */
            initPopin: function () {
                this.popin = PopinManager.createPopIn({
                    position: { my: "right top", at: "right top+" + jQuery(Core.get('wrapper_toolbar_selector')).height()}
                });

                this.popin.setTitle(translator.translate('page_status'));
                this.popin.addOption('width', 140);
            },

            /**
             * Create the popin if not exist and show
             * @returns {undefined}
             */
            show: function () {

                if (this.popin === undefined) {
                    this.initPopin();
                }

                this.popin.display();

                this.updateContent();
            },

            updateContent: function () {
                var self = this;

                if (this.popin && this.defaultContent === undefined) {

                    this.popin.mask();

                    PageRepository.findCurrentPage().done(function (page) {
                        var data = {
                                'page': page,
                                'templates': []
                            };

                        Core.Mediator.publish('on:informationPage:preRender', data, self.popin);

                        page.modified = moment.unix(parseInt(page.modified, 10)).format('H:m DD/MM/YYYY');
                        self.setContent(Renderer.render(template, data));
                        self.popin.unmask();
                    });
                }
            },

            /**
             * Set content to the popin
             * @param {type} html
             * @returns {undefined}
             */
            setContent: function (html) {
                this.popin.setContent(html);
            },

            /**
             * Destroy the popin and unset this.popin
             */
            hide: function () {
                PopinManager.destroy(this.popin);

                delete this.popin;
            }
        });

        return new JS.Singleton(InformationPageWidget);
    }
);
