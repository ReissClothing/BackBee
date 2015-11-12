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
        'content.dnd.manager',
        'content.mouseevent.manager',
        'content.save.manager',
        'content.manager',
        'content.container',
        'content.view.contribution.index',
        'content.view.edit.contribution.index',
        'definition.manager',
        'content.repository',
        'revision.repository',
        'keyword.repository',
        'component!revisionselector',
        'jquery',
        'content.widget.DialogContentsList',
        'component!notify',
        'content.widget.Edition'
    ],
    function (
        Core,
        DndManager,
        MouseEventManager,
        SaveManager,
        ContentManager,
        ContentContainer,
        ContributionIndexView,
        EditContributionIndexView,
        DefinitionManager,
        ContentRepository,
        RevisionRepository,
        KeywordRepository,
        RevisionSelector,
        jQuery,
        DialogContentsList,
        notify,
        Edition
    ) {
        'use strict';

        Core.ControllerManager.registerController('MainController', {
            appName: 'content',

            EDITABLE_ELEMENTS: ['Element/Text'],

            config: {
                imports: ['content.repository'],
                define: {
                    editionService: ['content.widget.Edition', 'content.manager'],
                    getPluginManagerService: ['content.pluginmanager'],
                    saveService: ['component!popin', 'component!translator'],
                    cancelService: ['component!translator'],
                    validateService: ['component!translator']
                }
            },

            /**
             * Initialize of Bundle Controller
             */
            onInit: function () {
                this.repository = require('content.repository');
            },

            computeImagesInDOMService: function () {
                ContentManager.computeImages('body');
            },

            addDefaultZoneInContentSetService: function () {
                Core.Scope.subscribe('block', function () {
                    ContentManager.addDefaultZoneInContentSet(true);
                }, function () {
                    ContentManager.addDefaultZoneInContentSet(false);
                });

                Core.Scope.subscribe('content', function () {
                    ContentManager.addDefaultZoneInContentSet(true);
                }, function () {
                    ContentManager.addDefaultZoneInContentSet(false);
                });
            },

            getSelectedContentService: function () {
                var content = null,
                    nodeSelected = jQuery('.bb-content-selected');

                if (nodeSelected.length > 0) {
                    content = ContentManager.getContentByNode(nodeSelected);
                }

                return content;
            },

            /**
             * Return the content repository
             */
            getRepositoryService: function () {
                return this.repository;
            },

            getKeywordRepositoryService: function () {
                return KeywordRepository;
            },

            /**
             * Return the dialog content list widget
             */
            getDialogContentsListWidgetService: function () {
                return DialogContentsList;
            },

            /**
             * Return the definition manager
             */
            getDefinitionManagerService: function () {
                return DefinitionManager;
            },

            /**
             * Return the definition manager
             */
            getContentManagerService: function () {
                return ContentManager;
            },

            getContentContainerService: function () {
                return ContentContainer;
            },

            getPluginManagerService: function (req) {
                return req('content.pluginmanager');
            },

            getSaveManagerService: function () {
                return SaveManager;
            },

            editionService: function (req) {
                var EditionHelper = req('content.widget.Edition'),
                    ContentHelper = req('content.manager');

                return {
                    EditionHelper: EditionHelper,
                    ContentHelper: ContentHelper
                };
            },

            /**
             * Call method save into SaveManager
             */
            saveService: function (req, confirm) {
                var translator = req('component!translator'),
                    nbContents = SaveManager.getContentsToSave().length,
                    dfd = new jQuery.Deferred();

                if (confirm !== true) {
                    SaveManager.save().done(function () {
                        dfd.resolve();
                    });
                } else {

                    if (nbContents > 0) {
                        SaveManager.save().done(function () {
                            notify.success(nbContents + ' ' + translator.translate('content_saved_sentence' + ((nbContents > 1) ? '_plural' : '')));
                            dfd.resolve();
                        });
                    } else {
                        notify.warning(translator.translate('no_content_save'));
                        dfd.resolve();
                    }

                }

                return dfd.promise();
            },

            /**
             * Show the revision selector
             * @returns {undefined}
             */
            cancelService: function (req) {
                var translator = req('component!translator'),
                    config = {
                        popinTitle: translator.translate('cancel_confirmation'),
                        noContentMsg: translator.translate('no_content_cancel'),
                        title: translator.translate('cancel_changes_content_below') + ' :',
                        onSave: function (data, popin) {
                            popin.mask();
                            RevisionRepository.save(data, 'revert').done(function () {
                                if (data.length === 0) {
                                    notify.warning(translator.translate('no_content_cancel'));
                                } else {
                                    notify.success(translator.translate('contents_canceled'));
                                    location.reload();
                                }

                                popin.unmask();
                                popin.hide();
                            });
                        }

                    };

                new RevisionSelector(config).show();
            },

            /**
             * Show the revision selector
             * @returns {undefined}
             */
            validateService: function (req) {
                var translator = req('component!translator'),
                    config = {
                        popinTitle: translator.translate('validation_confirmation'),
                        noContentMsg: translator.translate('no_content_validate'),
                        noteMsg: translator.translate('validation_popin_note') + '<br />' + translator.translate('validation_unselect_note'),
                        title: translator.translate('confirm_saving_changes_content_below') + ' :',
                        onSave: function (data, popin) {
                            popin.mask();
                            RevisionRepository.save(data, 'commit').done(function () {
                                if (data.length === 0) {
                                    notify.warning(translator.translate('no_content_validate'));
                                } else {
                                    notify.success(translator.translate('contents_validated'));
                                }

                                popin.unmask();
                                popin.hide();
                            });
                        }
                    };

                new RevisionSelector(config).show();
            },

            getEditableContentService: function (content) {
                var self = this,
                    dfd = new jQuery.Deferred(),
                    element,
                    result = [];

                if (jQuery.inArray(content.type, this.EDITABLE_ELEMENTS) !== -1) {
                    result.push(content);
                    dfd.resolve(result);
                } else {
                    content.getData('elements').done(function (elements) {
                        jQuery.each(elements, function (subContentName) {
                            element = elements[subContentName];
                            if (null === element) {
                                return true;
                            }

                            if (jQuery.inArray(element.type, self.EDITABLE_ELEMENTS) === -1) {
                                return true;
                            }
                            result.push(ContentManager.buildElement(element));
                        });
                        dfd.resolve(result);
                    });
                }

                return dfd.promise();
            },

            contributionIndexAction: function () {

                var self = this;

                Core.ApplicationManager.invokeService('contribution.main.index').done(function (service) {
                    service.done(function () {
                        Core.Scope.register('contribution', 'block');

                        if (self.contribution_loaded !== true) {
                            ContentRepository.findCategories().done(function (categories) {
                                var view = new ContributionIndexView({
                                    'categories': categories
                                });
                                view.render();

                                self.contribution_loaded = true;

                                DndManager.initDnD();

                                Core.ApplicationManager.invokeService('contribution.main.manageTabMenu', '#edit-tab-block');
                            });
                        } else {
                            Core.ApplicationManager.invokeService('contribution.main.manageTabMenu', '#edit-tab-block');
                        }
                    });
                });
            },

            contributionEditAction: function () {
                var self = this;

                Core.ApplicationManager.invokeService('contribution.main.index').done(function (service) {
                    service.done(function () {
                        Core.Scope.register('contribution', 'content');

                        if (self.contribution_edit_loaded !== true) {
                            var view = new EditContributionIndexView();
                            view.render();

                            DndManager.initDnD();
                            self.contribution_edit_loaded = true;
                        }

                        Core.ApplicationManager.invokeService('contribution.main.manageTabMenu', '#edit-tab-content');
                    });
                });
            },

            createView: function (Constructor, config, render) {
                var view = new Constructor(config);

                if (render) {
                    view.render();
                }
            },

            findDefinitionsService: function (page_uid) {
                return require('content.repository').findDefinitions(page_uid);
            },

            listenDOMService: function (definitions) {
                DefinitionManager.setDefinitions(definitions);

                Core.Scope.subscribe('content', function () {
                    DndManager.bindEvents();
                    MouseEventManager.enable(true);
                }, function () {
                    DndManager.unbindEvents();
                    MouseEventManager.enable(false);
                });

                Core.Scope.subscribe('block', function () {
                    DndManager.bindEvents();
                    MouseEventManager.enable(true);
                }, function () {
                    DndManager.unbindEvents();
                    MouseEventManager.enable(false);
                });

                MouseEventManager.listen();
            },

            showContentSelectorService: function () {
                var self = this;
                if (!this.ContentSelectorIsLoaded) {
                    require(['component!contentselector'], function (ContentSelector) {
                        if (self.ContentSelectorIsLoaded) { return; }
                        self.contentSelector = ContentSelector.createContentSelector({
                            mode: 'view',
                            resetOnClose: true
                        });
                        self.contentSelectorIsLoaded = true;
                        self.contentSelector.setContenttypes([]);
                        self.contentSelector.display();
                    });
                } else {
                    self.contentSelector.display();
                }
            },

            getContentPopins: function () {
                return Core.get('application.contribution').getPopins();
            },

            registerPopinService: function (id, popin) {
                this.getContentPopins()[id] = popin;
            },

            removePopinService: function (id) {
                delete this.getContentPopins()[id];
            },

            getPopinService: function (id) {
                return this.getContentPopins()[id];
            },

            getEditionWidgetService: function () {
                return Edition;
            }
        });
    }
);
