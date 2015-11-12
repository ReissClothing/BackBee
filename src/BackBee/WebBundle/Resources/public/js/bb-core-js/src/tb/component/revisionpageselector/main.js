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
        'revisionpageselector.templates': 'src/tb/component/revisionpageselector/templates'
    }
});

define(
    'tb.component/revisionpageselector/main',
    [
        'Core',
        'Core/Renderer',
        'component!popin',
        'component!translator',
        'text!revisionpageselector.templates/tree.twig',
        'text!revisionpageselector.templates/item.twig',
        'jquery',
        'jsclass'
    ],
    function (Core, Renderer, PopinManager, Translator, treeTemplate, itemTemplate, jQuery) {

        'use strict';

        var popinConfig = {
                width: 944,
                height: 'auto'
            },

            RevisionPageSelector = new JS.Class({

                revisionSelectorClass: '.bb-revision-page-selector',

                /**
                 * Initialize of Revision manage
                 */
                initialize: function (config) {
                    var self = this;

                    this.config = config;

                    this.buildPopin();

                    this.selector = '#' + this.popin.getId() + ' ' + this.revisionSelectorClass;

                    Core.ApplicationManager.invokeService('page.main.getSaveManager').done(function (SaveManager) {
                        self.SaveManager = SaveManager;
                    });
                },

                /**
                 * Build the popin
                 */
                buildPopin: function () {
                    var title = (this.config.popinTitle !== undefined) ? this.config.popinTitle : '';

                    this.popin = PopinManager.createPopIn({
                        position: { my: "center top", at: "center top+" + jQuery('#' + Core.get('menu.id')).height()}
                    });
                    this.popin.setTitle(title);
                    this.popin.addOptions(popinConfig);
                },

                getCheckedData: function () {
                    var data = jQuery(this.selector + ' input[data-savable="true"]:checked'),
                        result = [];

                    data.each(function () {
                        result.push(jQuery(this).data('key'));
                    });

                    return result;
                },

                /**
                 * Apply the save to the callback
                 */
                save: function () {
                    if (this.config.hasOwnProperty('onSave')) {
                        this.getCheckedData();
                        this.config.onSave(this.getCheckedData(), this.popin);
                    }
                },

                renderItems: function (items) {
                    var key,
                        template = '';


                    if (items !== undefined) {

                        for (key in items) {
                            if (items.hasOwnProperty(key)) {
                                template = template + Renderer.render(itemTemplate, {'item': items[key]});
                            }
                        }
                    }

                    return template;
                },

                /**
                 * Show popin with revisions
                 */
                show: function () {
                    this.popin.display();

                    var self = this,
                        config = {
                            items: self.renderItems(this.SaveManager.validateData(self.config.currentPage)),
                            title: self.config.title,
                            noContentMsg: self.config.noContentMsg
                        },
                        buttonName = 'Ok';

                    if (config.items.length > 0) {
                        buttonName = Translator.translate('confirm');
                    }

                    self.popin.addButton(buttonName, jQuery.proxy(self.save, self));
                    self.popin.setContent(Renderer.render(treeTemplate, config));
                }
            });

        return {
            create: function (config) {
                return new RevisionPageSelector(config);
            }
        };
    }
);
