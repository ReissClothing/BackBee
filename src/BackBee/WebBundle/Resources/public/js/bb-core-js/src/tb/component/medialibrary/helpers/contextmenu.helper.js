define(['component!contextmenu', 'jquery', 'component!notify'], function (ContextMenu, jQuery, notify) {
    'use strict';
    var treeView = null,
        contextMenu = null,
        selectedNode = null,
        mainWidget = null,
        mediaFolderStore = null,
        cuttedNode = null,
        trans = require('Core').get('trans') || function (value) { return value; },
        buildContextMenu = function () {
            var mediaFolderContextMenu = new ContextMenu({domTag: "#bb5-ui"}),
                actions = {
                    createAction: function () {
                        treeView.createNode();
                    },

                    editAction: function () {
                        treeView.editNode(selectedNode);
                    },

                    removeAction: function () {
                        mediaFolderStore.remove(selectedNode).done(function () {
                            treeView.removeNode(selectedNode);
                        }).fail(function (response) {
                            notify.error(response);
                        });
                    },

                    cutAction: function () {
                        cuttedNode = selectedNode;
                    },

                    showMediaFormAction: function (mediaType) {
                        mainWidget.showMediaEditForm(mediaType);
                    },

                    pasteAction: function (position) {

                        var data = {},
                            nextSibling,
                            nbChild,
                            moveNode;

                        /* Dealing with position */
                        if (position === "before") {
                            data.sibling_uid = selectedNode.uid;
                        }
                        if (position === 'inside') {
                            data.parent_uid = selectedNode.uid;
                        }
                        if (position === "after") {
                            nextSibling = selectedNode.getNextSibling();
                            if (nextSibling) {
                                data.sibling_uid = nextSibling.uid;
                            } else {
                                data.parent_uid = selectedNode.parent.uid;
                            }
                        }

                        /* Moving function */
                        moveNode = jQuery.proxy(function (cuttedNode, selectedNode, position, data) {

                            if (position === "inside") {
                                mediaFolderStore.moveNode(cuttedNode, data).done(function () {
                                    nbChild = selectedNode.children.length;
                                    if (nbChild) {
                                        treeView.moveNode(cuttedNode, selectedNode.children[nbChild - 1], 'after');//move as last child
                                    } else {
                                        treeView.moveNode(cuttedNode, selectedNode, position);
                                    }
                                    treeView.invoke("openNode", selectedNode);//refresh
                                }).fail(function (response) { notify(response); });
                            } else {

                                mediaFolderStore.moveNode(cuttedNode, data).done(function () {
                                    treeView.moveNode(cuttedNode, selectedNode, position);
                                }).fail(function (response) { notify(response); });
                            }

                        }, this, cuttedNode, selectedNode, position, data);

                        /*do Move */
                        moveNode();
                        cuttedNode = null;
                    }
                },
                nomalizeMediaType = function (name) {
                    return name.replace('/', '-');
                },

                buildMediaItems = function (contextMenu, mediaList) {
                    var item;
                    if (jQuery.isArray(mediaList)) {
                        jQuery.each(mediaList, function (i) {
                            item = mediaList[i];
                            contextMenu.addMenuItem({
                                btnCls: "bb5-contextmenu-" + nomalizeMediaType(item.type),
                                btnLabel: trans("create_a_new_media") + " " + item.title,
                                btnCallback: jQuery.proxy(actions.showMediaFormAction, this, item.type)
                            });
                        });
                    }
                };
            mediaFolderContextMenu.beforeShow = function () {

                if (selectedNode.children.length > 0 && !treeView.isNodeOpened(selectedNode) && !selectedNode.isLoaded) {
                    this.addFilter("bb5-context-menu-add");
                }
                if (!cuttedNode || (cuttedNode.uid === selectedNode.uid)) {
                    this.addFilter("bb5-context-menu-paste");
                    this.addFilter("bb5-context-menu-paste-before");
                    this.addFilter("bb5-context-menu-paste-after");
                }
                if (cuttedNode && cuttedNode.uid === selectedNode.uid) {
                    this.addFilter("bb5-context-menu-cut");
                }
                if (treeView.isRoot(selectedNode)) {
                    this.addFilter("bb5-context-menu-cut");
                    this.addFilter("bb5-context-menu-remove");
                    this.addFilter("bb5-context-menu-paste-before");
                    this.addFilter("bb5-context-menu-paste-after");
                }
                if (cuttedNode && cuttedNode.parent.uid === selectedNode.uid) {
                    this.addFilter("bb5-context-menu-paste");
                }
            };

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-add",
                btnLabel: trans("create_a_folder"),
                btnCallback: actions.createAction
            });

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-edit",
                btnLabel: trans("edit"),
                btnCallback: actions.editAction
            });

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-remove",
                btnLabel: trans("remove"),
                btnCallback: actions.removeAction
            });

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-cut",
                btnLabel: trans("cut"),
                btnCallback: actions.cutAction
            });

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-paste-before",
                btnLabel: trans("paste_before"),
                btnCallback: jQuery.proxy(actions.pasteAction, this, "before")
            });

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-paste",
                btnLabel: trans("paste"),
                btnCallback: jQuery.proxy(actions.pasteAction, this, "inside")
            });

            mediaFolderContextMenu.addMenuItem({
                btnCls: "bb5-context-menu-paste-after",
                btnLabel: trans("paste_after"),
                btnCallback: jQuery.proxy(actions.pasteAction, this, "after")
            });

            buildMediaItems(mediaFolderContextMenu, mainWidget.getAvailableMedia());
            mediaFolderContextMenu.enable();
            return mediaFolderContextMenu;
        };
    return {
        setMainWidget: function (widget) {
            treeView = widget.mediaFolderTreeView;
            mediaFolderStore = widget.mediaFolderDataStore;
            mainWidget = widget;
        },

        setSelectedNode: function (node) {
            selectedNode = node;
        },

        getContextMenu: function () {
            if (!contextMenu) {
                contextMenu = buildContextMenu();
            }
            return contextMenu;
        }
    };
});