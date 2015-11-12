define(
    [
        'Core',
        'jquery',
        'Core/Renderer',
        'text!contribution/tpl/index'
    ],
    function (Core, jQuery, Renderer, template) {

        'use strict';

        /**
         * View of contribution's index
         * @type {Object} Backbone.View
         */
        var BundleViewIndex = Backbone.View.extend({

            id: 'contribution-tab',

            initialize: function (config) {

                this.alreadyLoaded = false;
                if (config !== undefined) {
                    this.alreadyLoaded = (config.alreadyLoaded === true);
                }
            },

            /**
             * Events of view
             */
            bindEvents: function () {
                var element = jQuery('#' + this.id);

                element.on('click', 'ul#edit-tab li', this.manageMenu);
                element.on('click', '#new-page', this.showNewPage);
                element.on('click', '.global-save', this.manageSave);
                element.on('click', '#bundle-toolbar-tree', this.showTree);
                element.on('click', '#bundle-toolbar-contentSelector', this.showContentSelector);
                element.on('click', '.bundle-toolbar-global-validate', this.manageValidate);
                element.on('click', '.bundle-toolbar-global-cancel', this.manageCancel);
                element.on("click", "#btn-show-mediaLibrary", this.showMediaLibrary);
                element.on("click", "#keyword-editor", this.showKwEditor.bind(this));
            },

            showContentSelector: function () {
                var popinId = 'bb-page-content-selector',
                    config = {
                        do_loading: true,
                        do_pagination: true,
                        site_uid: Core.get('site.uid'),
                        popin: true
                    };
                if (document.getElementById(popinId) !== null) {
                    jQuery('#' + popinId).dialog('open');
                } else {
                    Core.ApplicationManager.invokeService('content.main.showContentSelector', config);
                }
            },

            showTree: function () {
                var popinId = 'bb-page-tree',
                    treePromise,
                    config = {
                        do_loading: true,
                        do_pagination: true,
                        site_uid: Core.get('site.uid'),
                        popin: true,
                        autoLoadRoot: true
                    };
                if (document.getElementById(popinId) !== null) {
                    jQuery('#' + popinId).dialog('open');
                } else {
                    treePromise = Core.ApplicationManager.invokeService('page.main.tree', config);
                    treePromise.done(function (pageTreeContribution) {
                        pageTreeContribution.view.on("rootIsLoaded", function () {
                            pageTreeContribution.view.showFilter();
                            pageTreeContribution.selectPage(Core.get("page.uid"));
                        });

                    });
                }
            },

            showNewPage: function () {
                var popinId = 'bb-new-page',
                    config = {
                        'parent_uid': Core.get('page.uid'),
                        'flag': 'redirect',
                        'popinId': popinId,
                        'from_page': true
                    };
                if (document.getElementById(popinId) !== null) {
                    jQuery('#' + popinId).dialog('open');
                } else {
                    Core.ApplicationManager.invokeService('page.main.newPage', config);
                }

            },

            showMediaLibrary: function (config) {
                return Core.ApplicationManager.invokeService('contribution.main.showMediaLibrary', config);
            },

            showKwEditor: function () {
                Core.ApplicationManager.invokeService('contribution.main.showKeywordEditor');
            },

            manageMenu: function (event) {
                Core.ApplicationManager.invokeService('contribution.main.manageTabMenu', jQuery(event.currentTarget));
            },

            /**
             * Call service `save` into main application
             */
            manageSave: function () {
                Core.ApplicationManager.invokeService('main.main.save');
            },

            /**
             * Call service `validate` into main application
             */
            manageValidate: function () {
                Core.ApplicationManager.invokeService('main.main.validate');
            },

            /**
             * Call service `cancel` into main application
             */
            manageCancel: function () {
                Core.ApplicationManager.invokeService('main.main.cancel');
            },

            /**
             * Render the template into the DOM with the Renderer
             * @returns {Object} BundleViewIndex
             */
            render: function () {
                var self = this,
                    dfd = jQuery.Deferred();

                Core.ApplicationManager.invokeService('main.main.toolbarManager').done(function (Service) {
                    Service.append('contribution-tab', Renderer.render(template, this.contribution));

                    if (self.alreadyLoaded !== true) {
                        self.bindEvents();
                    }

                    dfd.resolve();
                });

                return dfd.promise();
            }
        });

        return BundleViewIndex;
    }
);
