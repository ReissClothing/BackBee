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
        'ls-templates': 'src/tb/component/linkselector/templates'
    }
});

define(
    [
        'Core',
        'Core/Renderer',
        'jquery',
        'component!popin',
        'component!translator',
        'component!dataview',
        'component!datastore',
        'component!pagination',
        'component!rangeselector',
        'component!formbuilder',
        'component!mask',
        'text!ls-templates/layout.twig',
        'text!ls-templates/page-item.twig',
        'component!siteselector',
        'jquery-layout',
        'jsclass'
    ],
    function (Core,
            Renderer,
            jQuery,
            PopinManager,
            Translator,
            DataView,
            DataStore,
            Paginator,
            RangeSelector,
            FormBuilder,
            Mask,
            layoutTemplate,
            pageItemTemplate
            ) {

        'use strict';

        var LinkSelector = new JS.Class({

            layoutSelector: '.link-selector',
            treeSelector: '.link-selector-tree .bb5-treeview',
            bodySelector: '.link-selector-body',
            btnSelectSelector: '.select-btn',
            paginationSelector: '.link-selector-selection-pagination',
            resultInfosSelector: '.result-infos',
            rangeSelectorSelector: '.max-per-page-selector',
            menuSelector: '.link-selector-menu',
            internalLinkSelector: '.link-selector-internal-link',
            externalLinkSelector: '.link-selector-external-link',
            wrapperAreaSelector: '.link-selector-wrapper-area',

            treeConfig: {
                do_loading: true,
                do_pagination: true,
                site_uid: Core.get('site.uid')
            },

            pagintationConfig: {
                itemsOnPage: 10
            },

            rangeConfig: {
                range: [5, 45, 5]
            },

            dataStoreConfig: {
                resourceEndpoint: 'page'
            },

            initInternal: function () {
                this.initPopin();
                this.initDataStore();
                this.initDataView();
                this.initPagination();
                this.initRangeSelector();

                this.maskManager = Mask.createMask({});
            },

            createSiteSelector: function () {
                var self = this,
                    siteSelectorCtn = jQuery(this.widget).find('.site-selector-ctn').eq(0);
                this.siteSelector = require("component!siteselector").createSiteSelector({selected : Core.get("site.uid") });

                this.siteSelector.on("ready", function () {
                    self.treeConfig.site_uid = this.getSelectedSite();
                    self.loadTree(self);
                });

                this.siteSelector.on("siteChange", this.handleSiteChange.bind(this));

                jQuery(siteSelectorCtn).replaceWith(this.siteSelector.render());
            },


            handleSiteChange: function (siteUid) {
                this.mask();
                this.treeConfig.site_uid = siteUid;
                this.dataStore.setStart(0).setLimit(this.pagination.getItemsOnPage());
                this.loadTree(this, false);
                this.dataView.reset();
                this.widget.find(this.resultInfosSelector).html("");
            },

            loadTree: function (Selector) {

                Core.ApplicationManager.invokeService('page.main.getPageTreeViewInstance').done(function (PageTreeView) {
                    var pageTree = new PageTreeView(Selector.treeConfig);
                    pageTree.getTree().done(function (tree) {

                        Selector.tree = tree;

                        Selector.tree.render(Selector.widget.find(Selector.treeSelector));
                        if (!Selector.isLoaded) {
                            Selector.onReady();
                            Selector.manageMenu();
                            Selector.bindTreeEvents();
                        }
                        /* rebind event */
                        Selector.tree.on('click', Selector.onTreeClick.bind(Selector));

                        /* load root node */
                        Selector.loadRootNode();

                        Selector.isLoaded = true;
                    }).always(function () {
                        Selector.unMask();
                    });
                });
            },

            loadRootNode: function () {
                var tree = this.tree.getRootNode();
                this.tree.invoke("openNode", tree.children[0]);
            },

            initExternal: function () {
                var self = this,
                    config = {
                        elements: {
                            url: {
                                type: 'url',
                                label: 'URL'
                            }
                        },
                        form: {
                            submitLabel: Translator.translate('select'),
                            onSubmit: function (data) {
                                self.close(data);
                            },
                            onValidate: function (form, data) {
                                var urlPattern = new RegExp(/^(https?:\/\/){1}([\da-z\.\-]+)\.([a-z\.]{2,6})([\/\w \.\-]*)*\/?$/);

                                if (!data.hasOwnProperty('url') || data.url.trim().length === 0 || urlPattern.test(data.url)) {
                                    form.addError('url', Translator.translate('url_required'));
                                }
                            }
                        }
                    };

                FormBuilder.renderForm(config).done(function (html) {
                    jQuery(self.externalLinkSelector + ' ' + self.wrapperAreaSelector).html(html);
                });
            },

            show: function () {

                if (this.isShown !== true) {

                    this.initInternal();
                    this.initExternal();

                    this.widget = jQuery(Renderer.render(layoutTemplate)).clone();
                }

                this.popin.display();

                this.isShown = true;
            },

            initPopin: function () {
                this.popin = PopinManager.createPopIn();
                this.popin.setTitle(Translator.translate('link_selector_label'));
                this.popin.addOption('height', jQuery(window).height() - 40);
                this.popin.addOption('width', jQuery(window).width() - 40);
                this.popin.addOption('open', jQuery.proxy(this.onOpen, null, this));
            },

            manageMenu: function () {
                var li = this.widget.find(this.menuSelector + ' ul li'),
                    internalLinkPane = this.widget.find(this.internalLinkSelector),
                    externalLinkPane = this.widget.find(this.externalLinkSelector);

                li.on('click', function () {

                    var element = jQuery(this),
                        siblings = element.siblings('li');

                    internalLinkPane.addClass('hidden');
                    externalLinkPane.addClass('hidden');
                    siblings.removeClass('active');

                    if (element.data('pane') === 'external') {
                        externalLinkPane.removeClass('hidden');
                    } else {
                        internalLinkPane.removeClass('hidden');
                    }

                    element.addClass('active');
                });
            },

            onOpen: function (Selector) {
                if (Selector.isShown === true) {
                    return;
                }
                Selector.createSiteSelector();
                Selector.createSiteSelector = jQuery.noop;

                var internalLink = Selector.widget.find(Selector.internalLinkSelector);

                jQuery(this).html(Selector.widget);

                Selector.layout = internalLink.layout({
                    applyDefaultStyles: true,
                    closable: false
                });

                 /* fixing layout size */
                setTimeout(function () {
                    Selector.layout.resizeAll();
                    Selector.layout.sizePane("west", 201);
                }, 0);
            },




            onReady: function () {
                var bodyElement = this.widget.find(this.bodySelector),
                    paginationSelector = this.widget.find(this.paginationSelector),
                    rangeSelectorSelector = this.widget.find(this.rangeSelectorSelector);

                this.dataView.render(bodyElement);
                this.pagination.render(paginationSelector, 'replaceWith');
                this.rangeSelector.render(rangeSelectorSelector, 'replaceWith');

            },

            initPagination: function () {
                this.pagination = Paginator.createPagination(this.paginationConfig);
                this.dataStore.setStart(0).setLimit(this.pagination.getItemsOnPage());
            },

            initRangeSelector: function () {
                this.rangeSelector = RangeSelector.createPageRangeSelector(this.rangeConfig);
            },

            initDataStore: function () {
                this.dataStore = new DataStore.RestDataStore(this.dataStoreConfig);

                this.dataStore.addFilter("byParent", function (value, restParams) {

                    restParams.criterias = {
                        'state': [1, 2, 3],
                        'parent_uid': value
                    };

                    return restParams;
                });
            },

            initDataView: function () {
                var config = {
                        css: {
                            width: "auto",
                            height: "auto"
                        }
                    };

                config.itemRenderer = jQuery.proxy(this.itemRenderer, this);
                config.dataStore = this.dataStore;
                this.dataView = DataView.createDataView(config);
            },

            bindTreeEvents: function () {

                var self = this;

                this.dataStore.on('processing', function () {
                    self.mask();
                });

                this.dataStore.on('doneProcessing', function () {
                    self.unMask();
                });

                this.pagination.on("pageChange", function (page) {

                    var limit = self.pagination.getItemsOnPage(),
                        start = (page - 1) * limit,
                        seletectedNode = self.tree.getSelectedNode();

                    self.dataStore.setStart(start);

                    if (!seletectedNode || (seletectedNode && seletectedNode.isRoot)) {
                        return;
                    }

                    self.dataStore.execute();
                });

                this.rangeSelector.on("pageRangeSelectorChange", function (val) {
                    self.dataStore.setLimit(val);
                    self.pagination.setItemsOnPage(val); // -->will trigger pageChange
                });

                this.dataStore.on("dataStateUpdate", jQuery.proxy(this.updatePaginationInfos, this));
            },

            onTreeClick: function (event) {

                var self = this;

                if (event.node.is_fake === true) {
                    return;
                }

                this.dataStore.applyFilter('byParent', event.node.id);
                this.dataStore.setStart(0);
                self.dataStore.execute();
            },

            updatePaginationInfos: function () {
                var resultTotal = this.dataStore.getTotal();

                this.widget.find(this.resultInfosSelector).html(this.tree.getSelectedNode().name + ' - ' + resultTotal + ' item(s)');

                this.pagination.setItems(resultTotal);
            },

            itemRenderer: function (mode, item) {
                var self = this,
                    html = Renderer.render(pageItemTemplate, {'item': item, 'mode': mode}),
                    element = jQuery(html);

                element.on('click', this.btnSelectSelector, function () {
                    self.dataView.selectItems(item);
                    self.close({'pageUid': item.uid, 'url': item.url});
                });

                return element;
            },

            close: function (data) {
                var object = {};

                object.url = (data.url === undefined) ? null : data.url;
                object.pageUid = (data.pageUid === undefined) ? null : data.pageUid;

                this.popin.hide();

                this.trigger("close", object);
            },

            mask: function () {
                this.maskManager.mask(this.treeSelector);
            },

            unMask: function () {
                this.maskManager.unmask(this.treeSelector);
            }
        });

        return {
            create: function () {
                var linkSelector = new LinkSelector();

                jQuery.extend(linkSelector, {}, Backbone.Events);

                return linkSelector;
            }
        };
    }
);
