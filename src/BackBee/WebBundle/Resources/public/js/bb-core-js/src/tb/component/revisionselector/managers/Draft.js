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

define(
    [
        'Core',
        'Core/Renderer',
        'text!revisionselector.templates/item-wrapper.twig',
        'moment',
        'jquery',
        'jsclass'
    ],
    function (Core, Renderer, itemWrapperTemplate, Moment, jQuery) {

        'use strict';

        var DraftManager = new JS.Class({

            /**
             * Initalize of Draft manager
             */
            initialize: function () {
                var self = this;

                Core.ApplicationManager.invokeService('content.main.getDefinitionManager').done(function (DefinitionManager) {
                    self.DefinitionManager = DefinitionManager;
                });
            },

            /**
             * Build draft with new attributes
             * @param {Object} draft
             * @param {String} name
             * @param {Number} margin
             * @param {Boolean} haveSubs
             * @param {Object} parent
             * @returns {Object}
             */
            buildDraft: function (draft, name, margin, haveSubs, parent) {

                var hideChildren = false;

                draft.stateLabel = 'New';
                if (draft.state === 1002) {
                    draft.stateLabel = 'Modified';
                } else if (draft.state === 1003) {
                    draft.stateLabel = 'Conflict';
                    draft.error = true;
                }

                if (parent !== undefined) {
                    draft.parentUid = (parent.uid !== undefined) ? parent.uid : null;
                    draft.elementParentId = (typeof parent.elementId === 'string') ? parent.elementId : null;
                }

                if (draft.state === 1001) {
                    hideChildren = true;
                } else {
                    hideChildren = (draft.type !== undefined) ? (draft.type.substr(0, 7) === 'Element') : false;
                }


                draft.id = this.generateId();
                draft.uid = (draft.uid !== undefined) ? draft.uid : this.generateId();
                draft.isContentSet = (draft.type !== undefined) ? this.isContentSet(draft.type) : false;
                draft.elementId = (haveSubs === true || draft.isContentSet === true) ? this.generateId() : null;
                draft.name = (name === undefined) ? draft.type : name;
                draft.hideChildren = hideChildren;
                draft.hideHimself = (parent !== undefined) ? parent.hideChildren : false;
                draft.margin = margin;
                draft.paramId = (typeof draft.parameters === 'object' && Object.keys(draft.parameters).length > 0) ? this.generateId() : null;
                draft.modifiedString = (draft.modified !== undefined) ? Moment.utc(draft.modified, 'X').format('DD/MM/YYYY - HH:mm') : '';

                return draft;
            },

            /**
             * Generate a unique id
             * @returns {String}
             */
            generateId: function () {
                return Math.random().toString(36).substr(2);
            },

            /**
             * Search sub drafts of a draft recursively
             * @param {Object} draft
             * @param {Boolean} first
             * @param {Number} margin
             * @param {String} name
             * @returns {Array}
             */
            computeSubDraft: function (draft, first, margin, name, parent) {
                var i,
                    subDraftKeys,
                    keys = Object.keys(draft),
                    subDraft,
                    haveSubs = false,
                    subDrafts = [],
                    currentParent;

                if (margin === undefined) {
                    margin = 0;
                }

                for (i in draft) {

                    if (draft.hasOwnProperty(i)) {

                        subDraft = draft[i];
                        subDraftKeys = Object.keys(subDraft);
                        currentParent = this.searchDraft(keys[0], subDrafts);

                        if (first === true) {
                            haveSubs = (keys.length > 1 || Object.keys(subDraft.elements).length > 0);
                            subDrafts.push(this.buildDraft(subDraft, name, margin, haveSubs, parent));
                            margin = margin + 30;
                            first = false;
                        } else {
                            if (subDraftKeys.length > 1) {
                                if (subDraft.hasOwnProperty('uid')) {
                                    subDrafts.push(this.buildDraft(subDraft, i, margin, false, currentParent));
                                } else {
                                    jQuery.merge(subDrafts, this.computeSubDraft(subDraft, true, margin, name, currentParent));
                                }
                            } else {
                                subDrafts.push(this.buildDraft(subDraft[subDraftKeys[0]], i, margin, false, currentParent));
                            }
                        }
                        if (subDraft.hasOwnProperty('uid') && subDraft.hasOwnProperty('elements')) {
                            if (Object.keys(subDraft.elements).length > 0 && this.isContentSet(subDraft.type) === false) {
                                jQuery.merge(
                                    subDrafts,
                                    this.computeElements(
                                        subDraft.elements,
                                        margin,
                                        this.searchDraft(
                                            subDraft.uid,
                                            subDrafts
                                        )
                                    )
                                );
                            }
                        }
                    }
                }

                return subDrafts;
            },

            /**
             * Search draft by uid in array
             * @param {String} uid
             * @param {Array} array
             * @returns {undefined|Object}
             */
            searchDraft: function (uid, array) {
                var key,
                    draft,
                    result;

                for (key in array) {
                    if (array.hasOwnProperty(key)) {
                        draft = array[key];
                        if (draft.hasOwnProperty('uid')) {
                            if (draft.uid === uid) {
                                result = draft;
                                break;
                            }
                        }
                    }
                }

                return result;
            },

            /**
             * Verify if the type is a contentset
             * @param {String} type
             * @returns {Boolean}
             */
            isContentSet: function (type) {
                var definition,
                    isContentSet = false;

                if (type !== undefined) {

                    definition = this.DefinitionManager.find(type);

                    if (null !== definition) {
                        isContentSet = (definition.properties.is_container === true);
                    }
                }

                return isContentSet;
            },

            /**
             * Compute element of draft (scalar)
             * @param {Object} elements
             * @param {String} margin
             * @param {Object} parent
             * @returns {Array}
             */
            computeElements: function (elements, margin, parent) {
                var key,
                    element,
                    subDrafts = [];

                if (elements !== undefined) {
                    for (key in elements) {
                        if (elements.hasOwnProperty(key)) {

                            element = elements[key];
                            element.state = 1002;
                            element.name = key;
                            element.isScalar = true;

                            this.buildDraft(element, key, margin, true, parent);

                            if (element.isContentSet === false) {
                                subDrafts.push(this.buildDraft(element, key, margin, false, parent));
                            }
                        }
                    }
                }

                return subDrafts;
            },

            /**
             * Compute drafts and render them
             * @param {Object} drafts
             * @returns {String}
             */
            computeDraft: function (drafts) {
                var key,
                    draft,
                    html = '',
                    config,
                    start = 1;

                for (key in drafts) {
                    if (drafts.hasOwnProperty(key)) {
                        draft = drafts[key];
                        config = {
                            'items': this.computeSubDraft(draft, true),
                            'start': start,
                            'generateId': this.generateId
                        };

                        html = html + Renderer.render(itemWrapperTemplate, config);

                        start = start + 1;
                    }
                }

                return html;
            }
        });

        return new JS.Singleton(DraftManager);
    }
);