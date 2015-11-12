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
        'jquery',
        'jsclass'
    ],
    function (jQuery) {

        'use strict';

        /**
         * Event manager
         */
        var EventManager = new JS.Class({

            inputClass: '.revision-input',
            generalInputClass: '.general-revision-input',

            /**
             * Init of event manaager
             * @param {String} selector
             */
            init: function (selector) {
                this.tree = jQuery(selector);
                this.selector = selector;

                if (this.tree.length > 0) {
                    this.bindEvents();
                }
            },

            /**
             * Bind events
             */
            bindEvents: function () {
                this.tree.on('click', this.inputClass, jQuery.proxy(this.onClick, this));
                this.tree.on('click', this.generalInputClass, jQuery.proxy(this.onGeneralClick, this));
            },

            /**
             * On general click event
             * @param {Object} event
             */
            onGeneralClick: function (event) {
                var target = jQuery(event.currentTarget);

                this.tree.find(this.inputClass).prop('checked', target.prop('checked'));
            },

            /**
             * On click event
             * @param {Object} event
             */
            onClick: function (event) {
                var currentInput = jQuery(event.currentTarget);

                this.changeState(currentInput.prop('checked'), currentInput);
            },

            /**
             * Change state recursively of input
             * @param {Boolean} checked
             * @param {Object} currentInput
             * @param {Array} checkedInputs
             */
            changeState: function (checked, currentInput, checkedInputs) {
                var self = this,
                    parentId = currentInput.data('id'),
                    children = jQuery(this.selector + ' input[data-parent-id=' + parentId + ']');

                if (checkedInputs === undefined) {
                    checkedInputs = [];
                }

                this.checkParent(currentInput.data('parent-id'), checked);

                children.each(function () {
                    var element = jQuery(this),
                        id = element.data('id');

                    if (jQuery.inArray(id, checkedInputs) === -1) {
                        checkedInputs.push(id);
                        element.prop('checked', checked);
                        self.changeState(checked, element, checkedInputs);
                    }
                });
            },

            /**
             * Check the parent and change state
             * @param {String} parentId
             * @param {Boolean} state
             */
            checkParent: function (parentId, state) {
                if (parentId !== undefined && parentId !== '') {
                    var current = jQuery(this.selector + ' input[data-id=' + parentId + ']'),
                        children = jQuery(this.selector + ' input[data-parent-id=' + parentId + ']'),
                        flag = true;

                    children.each(function () {
                        var element = jQuery(this);

                        if (state !== element.prop('checked')) {
                            flag = false;

                            return false;
                        }
                    });

                    if (flag === true) {
                        current.prop('checked', state);
                        this.checkParent(current.data('parent-id'), state);
                    } else {
                        if (state === false) {
                            this.changeParentState(current.data('parent-id'), state);
                        }
                    }
                }
            },

            /**
             * Change state of parent
             * @param {String} parentId
             * @param {Boolean} state
             */
            changeParentState: function (parentId, state) {

                if (parentId !== undefined && parentId !== '') {

                    var current = jQuery(this.selector + ' input[data-id=' + parentId + ']');

                    current.prop('checked', state);

                    this.changeParentState(current.data('parent-id'), state);
                }
            }
        });

        return new JS.Singleton(EventManager);
    }
);