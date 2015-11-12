define(['jquery', 'BackBone', 'jsclass'], function (jQuery, BackBone) {
    'use strict';
    var NodeEditor = new JS.Class({
        CREATION_MODE: "create",
        EDIT_MODE: "edit",
        initialize: function (tree) {
            this.tree = tree;
            this.currentNode = null;
            this.isEditing = false;
            this.previousTitle = null;
            jQuery.extend(this, {}, BackBone.Events);
            this.formWrapper = this.createEditForm();
            this.editField = this.formWrapper.find('input').eq(0);
            this.currentEditor = null;
            this.bindEvents();
        },

        handleEdition: function () {
            var nodeValue = jQuery.trim(this.editField.val()),
                parentNode = null;

            if (nodeValue.length === 0 || nodeValue === jQuery.trim(this.previousTitle)) {
                this.cancelEdition();
                return;
            }
            this.currentNode.title = nodeValue;
            this.tree.invoke('updateNode', this.currentNode, nodeValue);
            this.formWrapper.hide();
            if (this.mode === this.CREATION_MODE) {
                parentNode = this.currentNode.parent;
                if (this.tree.isRoot(parentNode)) {
                    parentNode = null;
                }
            }
            this.trigger("editNode", jQuery.proxy(this.onEditCallback, this), this.currentNode, nodeValue, parentNode, this.tree);
        },

        onEditCallback: function (node) {
            if (this.mode === this.CREATION_MODE) {
                var editedNode = this.getEditedNode();
                /* replace editable node */
                node.label = node.title;
                node.isLoaded = true;
                this.tree.addNode(node, 'before', editedNode);
                this.tree.removeNode(editedNode);
                this.isEditing = false;
            }
        },

        getEditedNode: function () {
            return this.tree.getNodeById('node-editor');
        },

        createNode: function () {
            var selectedNode = this.tree.getSelectedNode(),
                node;
            if (this.isEditing) {
                return;
            }
            this.tree.appendNode({
                id: 'node-editor',
                label: ''
            }, selectedNode);

            selectedNode.hasFormNode = true; //hack
            if (!this.tree.isNodeOpened(selectedNode)) {
                this.tree.invoke('openNode', selectedNode);
            }
            node = this.tree.getNodeById('node-editor');
            this.edit(node);
        },

        disableEdition: function () {
            this.cancelEdition();
        },

        bindEvents: function () {
            var self = this;
            jQuery(this.tree.el).on('click', '.save-btn', jQuery.proxy(this.handleEdition, this));
            jQuery(this.tree.el).on('click', '.cancel-btn', jQuery.proxy(this.cancelEdition, this));
            this.tree.el.bind("tree.onRender", function () {
                self.tree.on("click", jQuery.proxy(self.disableEdition, self));
            });
        },

        createEditForm: function () {
            var divHtml = '<li class="jq-tree-editor"><input class="tree-editing-field"/> <i class="fa save-btn fa-check"></i> <i class="fa cancel-btn fa-close"></i></li>';
            return jQuery(divHtml);
        },

        edit: function (node) {
            this.mode = (node.id === "node-editor") ? this.CREATION_MODE : this.EDIT_MODE;

            if (this.isEditing) {
                jQuery(this.currentNode.element).show();
            }
            if (!node) {
                return false;
            }

            this.trigger('editing', node);
            this.isEditing = true;
            this.currentNode = node;
            this.previousTitle = this.currentNode.title;
            jQuery(node.element).hide();
            jQuery(node.element).after(this.formWrapper.show());
            this.editField.val(this.currentNode.title);
            this.editField.focus();
        },

        cancelEdition: function () {
            if (!this.isEditing) { return; }
            var formNode = this.tree.getNodeById('node-editor');
            if (formNode) {
                this.tree.removeNode(formNode);
            }
            this.isEditing = false;
            this.formWrapper.remove();
            if (this.currentNode && this.currentNode.id !== 'node-editor') {
                this.tree.invoke('updateNode', this.currentNode, this.previousTitle);
                this.currentNode.title = this.previousTitle;
                this.previousTitle = null;
                jQuery(this.currentNode.element).show();
            }
        }
    });
    return NodeEditor;
});