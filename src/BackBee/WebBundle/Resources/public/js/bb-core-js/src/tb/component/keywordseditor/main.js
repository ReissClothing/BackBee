/**
 * Keyword tree that allow us create and edit keywords
 **/


define(['Core', 'jquery', '../keywordseditor/datastore/keyword.datastore', '../keywordseditor/helper/contextmenu.helper', 'component!mask', 'component!notify', 'component!translator', 'component!treeview', 'jsclass'], function (Core, jQuery, KwDataStore, ContextMenuHelper, MaskManager, notify, TranslatorComponent, TreeViewComponent) {
    'use strict';

    var trans = Core.get("trans") || function (value) { return value; },

        KeywordEditor = new JS.Class({

            defaultConfig: {
                itemsByPage: 25,
                autoDisplay: false,
                dragAndDrop: true,
                title: trans("keywords_editor")
            },

            initialize: function (config) {
                this.config = jQuery.extend(true, {}, this.defaultConfig, config);
                this.itemsByPage = this.config.itemsByPage;
                this.config.open = this.onReady.bind(this);
                this.config.onCanMove = this.onCanMove.bind(this);
                this.config.onCanMoveTo = this.onCanMoveTo.bind(this);
                this.dialog = TreeViewComponent.createPopinTreeView(this.config);
                this.kwMap = {};
                this.isEditing = false;
            },

            initComponents: function () {
                this.contextMenuHelper = ContextMenuHelper;
            },

            onCanMoveTo: function (moved_node, target_node, position) {

                if (target_node.isPager && position === 'inside') {
                    return false;
                }

                if ((target_node.uid === target_node.root_uid) && position !== 'none') {
                    return false;
                }
                if (moved_node) {
                    return true;
                }
            },

            onCanMove: function (node) {
                if (node.isPager) {
                    return false;
                }
                return true;
            },

            onReady: function () {
                this.maskMng = MaskManager.createMask({});
                this.kwTree = this.dialog.treeView;
                this.treeContainer = jQuery(this.dialog.popinTemplate);
                this.initComponents();
                this.initDataStore();
                this.bindEvents();
                this.loadRoot();
            },

            bindEvents: function () {
                this.kwTree.on("open", this.loadNode.bind(this));
                this.kwTree.on("contextmenu", this.handleContextMenu.bind(this));
                this.kwTree.on("move", this.handleMove.bind(this));
                this.kwTree.on("click", this.handleClickOnPager.bind(this));

                this.contextMenuHelper.getContextMenu().on("contextmenu.create", this.createNewKw.bind(this));
                this.contextMenuHelper.getContextMenu().on("contextmenu.edit", this.editKeyword.bind(this));
                this.contextMenuHelper.getContextMenu().on("contextmenu.remove", this.removeNewKeyword.bind(this));
                this.kwTree.nodeEditor.on("editNode", this.handleNodeEdition.bind(this));
                this.kwTree.nodeEditor.on("editing", this.hidePager.bind(this));

                this.keywordStore.on("processing", this.showMask.bind(this));
                this.keywordStore.on("doneProcessing", this.hideMask.bind(this));
            },

            showMask: function () {
                this.dialog.popIn.mask();
            },

            hideMask: function () {
                this.dialog.popIn.unmask();
            },


            handleClickOnPager: function (e) {
                if (e.node.isPager) {
                    this.loadNode(e, true);
                }
            },

            handleMove: function (event) {

                var moveInfo = event.move_info,
                    kw_uid = moveInfo.moved_node.id,
                    nextSiblingNode,
                    data = {};
                event.move_info.do_move();
                if (moveInfo.moved_node.getNextSibling() !== null) {
                    data.sibling_uid = moveInfo.moved_node.getNextSibling().id;
                } else {
                    data.parent_uid =  moveInfo.moved_node.parent.id;
                }
                nextSiblingNode = moveInfo.moved_node.getNextSibling();
                if (nextSiblingNode && nextSiblingNode.isPager) {
                    data.sibling_uid =  moveInfo.moved_node.getPreviousSibling().id;
                    /* no sibling insert as last childen*/
                    if (!data.sibling_uid) {
                        data.parent_uid =  moveInfo.moved_node.parent.id;
                        delete data.sibling_uid;
                    }
                }
                this.keywordStore.moveNode(kw_uid, data).done(this.movePagerAsLastChild.bind(this, moveInfo.moved_node.parent));

            },

            handleNodeEdition: function (onEditCallBack, node, keyword, parentNode) {
                var self = this,
                    currentNodeInfos,
                    errorKey,
                    msg,
                    parentNodeUid = parentNode ? parentNode.uid : null,
                    jsonNode = {
                        uid: node.uid,
                        keyword: keyword,
                        parent_uid: parentNodeUid
                    };

                this.keywordStore.save(jsonNode).done(function () {
                    self.isEditing = false;

                    /* reload the tree */
                    self.keywordStore.find(jsonNode.uid).done(function (node) {
                        currentNodeInfos = self.formatData([node]);
                        onEditCallBack(currentNodeInfos[0]);
                        self.movePagerAsLastChild(parentNode);
                    });
                }).fail(function (response) {
                    response = JSON.parse(response);
                    errorKey = response.message.toLowerCase();
                    msg = TranslatorComponent.translate(errorKey) + " !";
                    self.kwTree.cancelEdition();
                    notify.error(msg);
                });
            },


            movePagerAsLastChild: function (parentNode) {
                var rootNode,
                    lastChild,
                    nbChildren,
                    pagerNode;
                if (!parentNode) {
                    rootNode = this.kwTree.getRootNode();
                    parentNode = rootNode.children[0];
                }

                nbChildren = parentNode.children.length;
                lastChild = parentNode.children[nbChildren - 1];

                pagerNode = this.kwTree.getNodeById("next-page" + parentNode.id);
                if (lastChild.isPager || !pagerNode) { return false; }
                this.kwTree.invoke("moveNode", pagerNode, lastChild, 'after');
                jQuery(pagerNode.element).show();
            },

            hidePager: function (editingNode) {
                var parentNode = this.kwTree.isRoot(editingNode) ? editingNode :  editingNode.parent,
                    pagerNode = this.kwTree.getNodeById("next-page" + parentNode.id);

                if (!pagerNode) { return false; }
                jQuery(pagerNode.element).hide();
            },


            createNewKw: function () {
                this.isEditing = true;
                this.kwTree.createNode();
            },

            removeNewKeyword: function (node) {
                var self = this,
                    msg,
                    parentNode = node.parent,
                    errorKey;
                this.keywordStore.remove(node).done(function () {
                    self.kwTree.removeNode(node);
                    self.handleRemoveKw(parentNode);
                }).fail(function (response) {
                    response = JSON.parse(response);
                    errorKey = response.message.toLowerCase();
                    msg = TranslatorComponent.translate(errorKey) + " !";
                    self.kwTree.cancelEdition();
                    notify.error(msg);
                });
            },

            handleRemoveKw: function (nodeParent) {

                if (!nodeParent) { return; }
                var pager = this.kwTree.getNodeById("next-page" + nodeParent.id);
                if (!pager) { return false; }

                pager.nextStart = pager.nextStart - 1;
                if (pager.nextStart < 0) {
                    pager.nextStart = 0;
                }

                /* reload when new items is needed */
                if (pager.nextStart === 0) {
                    this.loadNode({node : pager });
                }

            },


            editKeyword: function (node) {
                this.kwTree.editNode(node);
            },

            handleContextMenu: function (e) {
                this.contextMenuHelper.setSelectedNode(e.node);
                this.contextMenuHelper.show(e.click_event, this.kwTree);
                this.kwTree.invoke("selectNode", e.node);
            },

            loadRoot: function () {
                var self = this,
                    data;
                this.keywordStore.execute(true).done(function (response) {
                    data = self.formatData(response, true);
                    self.kwTree.setData(data);
                    self.autoloadRoot();
                });
            },

            autoloadRoot: function () {
                var rootNode = this.kwTree.getRootNode();
                this.kwTree.invoke("openNode", rootNode.children[0]);
            },

            loadNode: function (e) {

                var nodeParentUid;

                if (e.node.isLoaded || this.isEditing) { return false; }
                nodeParentUid = e.node.id;
                if (e.node.isPager) {
                    this.keywordStore.setStart(e.node.nextStart);
                    nodeParentUid = e.node.parent.id;
                }
                this.keywordStore.setLimit(this.itemsByPage).applyFilter("byParent", nodeParentUid).execute().done(this.kwProcessorHandler.bind(this, e.node));
            },


            kwProcessorHandler: function (cNode, response, header) {
                var data = this.formatData(response),
                    self = this,
                    pagerNode,
                    item;
                cNode.isLoaded = true;

                if (cNode.isPager) {
                    pagerNode = cNode;
                }
                cNode = cNode.isPager ? cNode.parent : cNode;

                this.handlePagination(cNode, data, header);

                if (pagerNode && pagerNode.isPager) {
                    this.kwTree.invoke('removeNode', pagerNode);
                    jQuery.each(data, function (i) {
                        item = data[i];
                        self.kwTree.invoke('appendNode', item, cNode);
                    });
                    return;
                }
                this.kwTree.setData(data, cNode);
            },

            formatData: function (rawKeywords) {
                var result = [],
                    self = this,
                    loadingMsg = TranslatorComponent.translate("loading"),
                    node;

                jQuery.each(rawKeywords, function (i) {
                    node = rawKeywords[i];
                    node.id = node.uid;
                    if (self.kwMap[node.id]) {
                        /* skip node render */
                        return true;
                    }
                    node.title = node.keyword;
                    node.label = node.keyword;

                    if (node.has_children) {
                        node.children = [{label : loadingMsg, is_fake: true}];
                    }
                    if (node.parent_uid) {
                        node.is_root = true;
                    }
                     /*
                      * As new nodes are appended as last child of their parent,
                      * we need a way to prevent them from being appended twice.
                      **/
                    self.kwMap[node.id] = node.id;
                    result.push(node);
                });

                return result;
            },

            handlePagination: function (parentNode, result, header) {

                if (header.getRangeTo() + 1 === header.getRangeTotal()) {
                    return;
                }
                var nextPageNode = {};
                nextPageNode.label = TranslatorComponent.translate("next_results") + "...";
                nextPageNode.id = "next-page" + parentNode.id;
                nextPageNode.is_fake = true;
                nextPageNode.isPager = true;
                nextPageNode.nextStart = header.getRangeTo() + 1;
                result.push(nextPageNode);
            },


            display: function () {
                this.dialog.display();
            },

            initDataStore: function () {
                this.keywordStore = KwDataStore.createKWDataStore();
                this.loaderKWStore = KwDataStore.createKWDataStore();
            }

        });



    return {

        createKeywordEditor: function (config) {
            return new KeywordEditor(config);
        },
        KeywordEditor: KeywordEditor
    };

});