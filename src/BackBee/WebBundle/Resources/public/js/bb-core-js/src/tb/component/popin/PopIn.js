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
define('tb.component/popin/PopIn', ['jquery', 'jsclass'], function (jQuery) {
    'use strict';

    /**
     * HIDDEN_STATE, OPEN_STATE and DESTROY_STATE are PopIn states constant possible value
     */
    var HIDDEN_STATE = 0,
        OPEN_STATE = 1,
        DESTROY_STATE = 2,

        /**
         * PopIn's class
         */
        PopIn = new JS.Class({

            /**
             * PopIn class constructor
             */
            initialize: function () {
                this.id = null;
                this.state = HIDDEN_STATE;
                this.content = '';
                this.options = {};
                this.children = [];
            },

            /**
             * Title property setter
             * @param {String} id
             * @return {PopIn} self
             */
            setId: function (id) {
                this.id = id;

                return this;
            },

            /**
             * Id property getter
             * @return {String}
             */
            getId: function () {
                return this.id;
            },

            /**
             * Title property setter
             * @param {String} title
             * @return {PopIn} self
             */
            setTitle: function (title) {
                this.options.title = title;
                return this;
            },

            /**
             * Title property getter
             * @return {String}
             */
            getTitle: function () {
                return this.options.title || '';
            },

            /**
             * Content property setter
             * @param {String} content
             * @return {PopIn} self
             */
            setContent: function (content) {
                this.content = content;
                return this;
            },

            /**
             * Content property getter
             * @return {String}
             */
            getContent: function () {
                return this.content;
            },

            /**
             * Add new child to children property
             * @param {PopIn} child
             * @throws raise exception if provided child is not a PopIn object
             * @return {PopIn} self
             */
            addChild: function (child) {
                if (child && typeof child === 'object' && typeof child.isA === 'function' && child.isA(PopIn) && false === child.isDestroy() && false === this.isDestroy()) {
                    this.children.push(child);
                } else {
                    throw 'PopIn::addChild only accept PopIn object which is not in destroy state.';
                }

                return this;
            },

            /**
             * Children property getter
             * @return {Object} object that contains every children of current pop-in
             */
            getChildren: function () {
                return this.children;
            },

            /**
             * Open current pop-in by changing its state to OPEN_STATE (= 1)
             * @return {PopIn} self
             */
            open: function () {
                if (this.state !== DESTROY_STATE) {
                    this.state = OPEN_STATE;
                }

                return this;
            },

            /**
             * Returns true if current pop-in state is equals to OPEN_STATE (= 1)
             * @return {Boolean}
             */
            isOpen: function () {
                return OPEN_STATE === this.state;
            },

            /**
             * Hides current pop-in by changing its state to HIDDEN_STATE (= 0)
             * @return {PopIn} self
             */
            close: function () {
                if (this.state !== DESTROY_STATE) {
                    this.state = HIDDEN_STATE;
                }

                return this;
            },

            /**
             * Returns true if current pop-in state is equals to HIDDEN_STATE (= 0)
             * @return {Boolean}
             */
            isClose: function () {
                return HIDDEN_STATE === this.state;
            },

            /**
             * Destroy current pop-in by changing its state and unset every properties except to state
             * @return {PopIn} self
             */
            destroy: function () {
                jQuery('#' + this.getId()).dialog("destroy");
                jQuery('#' + this.getId()).remove();
                this.state = DESTROY_STATE;
                delete this.id;
                delete this.content;
                delete this.options;
                delete this.children;
                return this;
            },

            /**
             * Returns true if current pop-in state is equals to DESTROY_STATE (= 2)
             * @return {Boolean}
             */
            isDestroy: function () {
                return DESTROY_STATE === this.state;
            },

            /**
             * Options property setter
             * @param {Object} options
             * @return {PopIn} self
             */
            setOptions: function (options) {
                this.options = options;

                return this;
            },

            /**
             * Add options
             * @param {Object} options
             * @return {PopIn} self
             */
            addOptions: function (options) {
                jQuery.extend(this.options, options);

                return this;
            },

            /**
             * Options property getter
             * @return {Object}
             */
            getOptions: function () {
                return this.options;
            },

            /**
             * Add new or override option into options property
             * @param {String} key
             * @param {Mixed}  value
             * @return {PopIn} self
             */
            addOption: function (key, value) {
                this.options[key] = value;

                return this;
            },

            /**
             * Add new button to current pop-in
             * @param {String}   label
             * @param {Function} callback
             * @return {PopIn} self
             */
            addButton: function (label, callback) {
                if (false === this.options.hasOwnProperty('buttons')) {
                    this.options.buttons = {};
                }

                this.options.buttons[label] = callback;

                jQuery('#' + this.getId()).dialog("option", "buttons", this.options.buttons);
                return this;
            },

            getDialog: function () {
                var dialog = null,
                    root = jQuery('#' + this.getId());
                if (root.is(':data(uiDialog)')) {
                    dialog = root.dialog("widget");
                }
                return dialog;
            },

            moveToTop: function () {
                if (this.isOpen()) {
                    jQuery('#' + this.getId()).dialog("moveToTop");
                }
            },

            /**
             * Class property setter
             * @param {String} dialogClass new class value
             */
            setClass: function (dialogClass) {
                this.options.dialogClass = dialogClass;
            },

            /**
             * Class property getter
             * @return {String} return class property
             */
            getClass: function () {
                return this.options.dialogClass || '';
            },

            /**
             * Enable modal behavior for this pop-in
             * @return {PopIn} self
             */
            enableModal: function () {
                this.options.modal = true;

                return this;
            },

            /**
             * Disable modal behavior for this pop-in
             * @return {PopIn} self
             */
            disableModal: function () {
                this.options.modal = false;

                return this;
            },

            /**
             * Returns true if this pop-in is a modal, else false
             * @return {Boolean}
             */
            isModal: function () {
                return this.options.modal || false;
            },

            /**
             * Enable resize behavior for this pop-in
             * @return {PopIn} self
             */
            enableResize: function () {
                this.options.resizable = true;

                return this;
            },

            /**
             * Disable resize behavior for this pop-in
             * @return {PopIn} self
             */
            disableResize: function () {
                this.options.resizable = false;

                return this;
            },

            /**
             * Returns true if this pop-in is resizable, else false
             * @return {Boolean}
             */
            isResizable: function () {
                return this.options.resizable || false;
            }
        });

    return PopIn;
});