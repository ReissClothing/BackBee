define(['Core', 'contribution.view.index', 'jquery'], function (Core, IndexView, jQuery) {

    'use strict';

    var trans = Core.get('trans') || function (value) {return value; };

    Core.ControllerManager.registerController('MainController', {
        appName: 'contribution',
        config: {
            imports: []
        },
        /**
         * Initialize of Contribution Controller
         */
        onInit: function () {
            this.mainApp = Core.get('application.main');
            this.mediaLibraryIsLoaded = false;
            this.kwEditorIsLoaded = false;
            this.mediaLibrary = null;
            this.kwEditor = null;
        },

        indexService: function () {
            var config = {},
                view;

            Core.ApplicationManager.invokeService('main.main.setTitlePane', trans('edition'));
            Core.Scope.register('contribution');

            if (this.viewIsLoaded !== true) {

                Core.Scope.subscribe('page', function () {
                    jQuery('.bb-content').addClass('bb-content-page-mode');

                    Core.ApplicationManager.invokeService('content.main.getSelectedContent').done(function (content) {
                        if (null !== content) {
                            content.unSelect();
                        }
                    });

                }, function () {
                    jQuery('.bb-content').removeClass('bb-content-page-mode');
                });

                this.viewIsLoaded = true;
            } else {
                config.alreadyLoaded = true;
            }

            view = new IndexView(config);

            return view.render();
        },

        manageTabMenuService: function (element) {
            element = jQuery(element);

            var type = element.children('a').data('type');

            jQuery('ul#edit-tab li.active').removeClass('active');
            element.addClass('active');

            jQuery('div#contrib-tab-apps div.tab-pane.active').removeClass('active');
            jQuery('div#' + type + '-contrib-tab').addClass('active');
        },

        /**
         * Index action
         * Show the edition toolbar
         */
        indexAction: function () {
            this.indexService();
        },


        showKeywordEditorService: function (config) {
            var self = this;

            if (!this.kwEditorIsLoaded) {
                require(["component!keywordseditor"], function (kwEditorComponent) {
                    if (self.kwEditorIsLoaded) { return; }
                    self.kwEditor = kwEditorComponent.createKeywordEditor(config);
                    self.kwEditor.display();
                    self.kwEditorIsLoaded = true;
                });
            } else {
                self.kwEditor.display();
            }


        },

        showMediaLibraryService: function (config) {
            var self = this;
            if (!this.mediaLibraryIsLoaded) {
                require(['component!medialibrary'], function (MediaLibraryComponent) {
                    if (self.mediaLibraryIsLoaded) { return; }
                    config.mode = "view";
                    self.mediaLibrary = MediaLibraryComponent.createMediaLibrary(config);
                    self.mediaLibraryIsLoaded = true;
                    self.mediaLibrary.display();
                });
            } else {
                self.mediaLibrary.display();
            }
        }
    });
});