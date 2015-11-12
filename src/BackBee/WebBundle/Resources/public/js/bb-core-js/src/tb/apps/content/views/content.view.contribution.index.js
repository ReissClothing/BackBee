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
        'jquery',
        'text!content/tpl/contribution/index',
        'text!content/tpl/carousel_blocks',
        'text!content/tpl/block_description',
        'Core/Renderer',
        'content.widget.DialogContentsList',
        'component!popin',
        'definition.manager',
        'component!translator',
        'bootstrap-carousel'
    ],
    function (Core,
              jQuery,
              template,
              carouselBlocksTpl,
              blockDescriptionTpl,
              Renderer,
              DialogContentsList,
              PopinManager,
              DefinitionManager,
              translator
            ) {

        'use strict';

        /**
         * View of content contribution index
         * @type {Object} Backbone.View
         */
        var ContentViewContributionIndex = Backbone.View.extend({

            /**
             * Point of Toolbar in DOM
             */
            el: '#block-contrib-tab',
            carouselBlocksId: '#carousel-contrib-blocks',
            carouselId: '#carousel-blocks-choice',
            selectCategoriesId: '#select-categories-blocks-contrib',
            paletteBlocksId: '#palette-contrib-blocks',
            carouselBlockClass: '.carousel-block',

            /**
             * Initialize of ContentViewContributionIndex
             */
            initialize: function (config) {
                this.categories = this.manageCategories(config.categories);

                this.bindUiEvents();
            },

            /**
             * Events of view
             */
            bindUiEvents: function () {
                var element = jQuery(this.el);

                element.on('change', this.selectCategoriesId, jQuery.proxy(this.onSelectCategory, this));
                element.on('click', this.paletteBlocksId, jQuery.proxy(this.onPaletteBlocksClick, this));
            },

            onPaletteBlocksClick: function () {
                if (this.widget === undefined) {
                    this.widget = new DialogContentsList({'draggable': true});
                }
                this.widget.show();
            },

            onSelectCategory: function (event) {
                var currentTarget = jQuery(event.currentTarget),
                    optionSelected = currentTarget.children('option:selected');

                this.showBlocksByCategory(optionSelected.val());

                jQuery(this.carouselId).carousel();
            },


            manageCategories: function (categories) {
                var key,
                    blockKey,
                    category,
                    block;

                for (key in categories) {
                    if (categories.hasOwnProperty(key)) {
                        category = categories[key];
                        category.show = false;

                        for (blockKey in category.contents) {
                            if (category.contents.hasOwnProperty(blockKey)) {
                                block = category.contents[blockKey];
                                if (block.visible) {
                                    category.show = true;
                                    break;
                                }
                            }
                        }
                    }
                }

                return categories;
            },

            getCategoryById: function (categoryId) {
                var key,
                    category,
                    result = null;

                for (key in this.categories) {
                    if (this.categories.hasOwnProperty(key)) {
                        category = this.categories[key];
                        if (category.id === categoryId) {
                            result = category;
                            break;
                        }
                    }
                }

                return result;
            },

            getAllBlocks: function () {
                var key,
                    blockKey,
                    category,
                    block,
                    blocks = [];

                for (key in this.categories) {
                    if (this.categories.hasOwnProperty(key)) {
                        category = this.categories[key];
                        for (blockKey in category.contents) {
                            if (category.contents.hasOwnProperty(blockKey)) {
                                block = category.contents[blockKey];
                                if (block.visible) {
                                    blocks.push(category.contents[blockKey]);
                                }
                            }
                        }
                    }
                }

                return blocks;
            },

            showBlocksByCategory: function (categoryId) {
                var category = this.getCategoryById(categoryId),
                    key,
                    data = {},
                    contents;

                data.blocks = [];
                data.active = true;

                if (null === category) {
                    contents = this.getAllBlocks();
                } else {
                    contents = category.contents;
                }

                for (key in contents) {
                    if (contents.hasOwnProperty(key)) {
                        if (contents[key].visible) {
                            data.blocks.push(contents[key]);
                        }
                    }
                }

                this.updateCarousel(data);
            },

            updateCarousel: function (data) {
                var carousel = jQuery(this.carouselBlocksId),
                    key,
                    html = '',
                    groupBlocks = [],
                    groupNumbers;

                groupNumbers = Math.ceil(data.blocks.length / 3);
                if (groupNumbers < 2) {
                    groupBlocks.push(data.blocks);
                } else {
                    for (key = 0; key < groupNumbers; key = key + 1) {
                        groupBlocks.push(data.blocks.slice((key * 3), (key * 3) + 3));
                    }
                }

                if (data.blocks.length > 0) {
                    delete data.blocks;
                    data.groupBlocks = groupBlocks;
                    html = html + Renderer.render(carouselBlocksTpl, data);
                }

                carousel.html(html);

                carousel.find(this.carouselBlockClass).on('click', jQuery.proxy(this.onBlockClick, this));
            },

            onBlockClick: function (event) {
                if (this.descriptionPopin === undefined) {
                    this.descriptionPopin = PopinManager.createPopIn();

                    this.descriptionPopin.setTitle(translator.translate('block_description'));
                    this.descriptionPopin.setId('bb-block-description');
                    Core.ApplicationManager.invokeService('content.main.registerPopin', 'blockDescription', this.descriptionPopin);
                }

                var target = jQuery(event.currentTarget),
                    type = target.data('bb-type'),
                    definition = DefinitionManager.find(type),
                    block = {
                        category: definition.properties.name,
                        description: definition.properties.description,
                        thumbnail: definition.image
                    };

                this.descriptionPopin.display();

                this.descriptionPopin.setContent(Renderer.render(blockDescriptionTpl, {'block': block}));
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {Object} PageViewContributionIndex
             */
            render: function () {

                jQuery(this.el).html(Renderer.render(template, {'categories': this.categories}));

                this.showBlocksByCategory('_all');
            }
        });

        return ContentViewContributionIndex;
    }
);