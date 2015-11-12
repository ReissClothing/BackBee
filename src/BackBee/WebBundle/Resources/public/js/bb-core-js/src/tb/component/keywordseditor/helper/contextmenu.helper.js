define(['component!contextmenu', 'BackBone', 'jquery'], function (ContextMenu, BackBone, jQuery) {
    'use strict';
    var keywordTree = null,
        selectedNode = null,
        kwContextMenu = null,
        trans = require('Core').get('trans') || function (value) { return value; },

        buildContextMenu = function () {
            kwContextMenu = new ContextMenu({
                domTag: "#bb5-ui"
            });
            jQuery.extend(kwContextMenu, {}, BackBone.Events);

            kwContextMenu.beforeShow = function () {
                if (selectedNode && keywordTree.isRoot(selectedNode)) {
                    this.addFilter("bb5-context-menu-remove");
                }
                if (selectedNode && selectedNode.isPager) {
                    this.addFilter("bb5-context-menu-remove");
                    this.addFilter("bb5-context-menu-add");
                    this.addFilter("bb5-context-menu-edit");
                }
            };

            kwContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-add",
                btnLabel: trans("create_a_keyword"),
                btnCallback: function () {
                    kwContextMenu.trigger("contextmenu.create", selectedNode);
                }
            });

            kwContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-edit",
                btnLabel: trans("edit"),
                btnCallback: function () {
                    kwContextMenu.trigger("contextmenu.edit", selectedNode);
                }
            });

            kwContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-remove",
                btnLabel: trans("remove"),
                btnCallback: function () {
                    kwContextMenu.trigger("contextmenu.remove", selectedNode);
                }
            });

            return kwContextMenu;
        };





    return {

        getContextMenu: function () {
            if (!kwContextMenu) {
                kwContextMenu = buildContextMenu();
            }
            return kwContextMenu;
        },

        setSelectedNode: function (node) {
            selectedNode = node;
        },

        show: function (event, kwTree) {
            keywordTree = kwTree;
            this.getContextMenu().enable();
            this.getContextMenu().show(event);
        }

    };


});