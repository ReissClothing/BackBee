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
        'jquery',
        'page.repository',
        'component!treeview',
        'component!mask',
        'component!translator',
        'component!siteselector'
    ],

    function (Core, jQuery, PageRepository, Tree, Mask, Translator) {



        'use strict';

        /**
        * View of new page
        * @type {Object} Backbone.View
        */
        var PageViewTree = Backbone.View.extend({

            limit_of_page: 25,

            /**
            * Initialize of PageViewClone
            */
            initialize: function (config) {
                this.config = config;
                this.config.popin_title = this.config.popin_title || Translator.translate('page_tree');

                if (typeof this.config.site_uid !== 'string') {
                    Core.exception('MissingPropertyException', 500, 'Property "site_uid" must be set to constructor');
                }

                this.isProcessing = false;
                this.maskMng = Mask.createMask();
                this.config.only_section = this.config.only_section || false;
                this.limit_of_page = this.config.limit_of_page || this.limit_of_page;
                this.site_uid = this.config.site_uid;
                this.enable_siteSelection = this.config.enable_siteSelection || false;
                this.initializeTree();

            },

            showFilter: function () {
                if (this.config.popin === true) {
                    this.tree.showFilter();
                }
            },

            initCallStack: function () {
                var self = this,
                    stack = {
                        queue: [],
                        processDfd: new jQuery.Deferred(),

                        hasTask: function () {
                            return this.queue.length;
                        },

                        execute: function () {
                            if (this.hasTask()) {
                                try {
                                    var task = this.queue.shift();
                                    /* we only queued getTree that returns a promise */
                                    task.call(self)
                                        .done(this.processDfd.resolve)
                                        .fail(function () {
                                            self.processDfd.reject();
                                        });
                                } catch (e) {
                                    this.processDfd.reject(e);
                                }
                            }
                        },
                        append: function (func) {
                            if (typeof func !== 'function') { return false; }
                            this.queue.push(func);
                            return this.processDfd.promise();
                        }
                    };

                return stack;
            },

            hasSiteSelector: function () {
                return this.enable_siteSelection;
            },

            getSelectedSite: function () {
                return this.selectedSite;
            },

            getAvailableSites: function () {
                return this.siteSelector.sites.length;
            },

            handleSiteSelector: function (widget) {
                var self = this;
                this.selectedSite = null;
                this.getCallStack = this.initCallStack();
                this.siteSelector = require("component!siteselector").createSiteSelector({selected : Core.get("site.uid") });

                this.siteSelector.on("ready", function () {
                    self.selectedSite = this.getSelectedSite(true);
                    self.site_uid = self.selectedSite.uid;
                    self.siteSelectorIsReady = true;
                    self.getCallStack.execute();
                });

                this.siteSelector.on("siteChange", function (site_uid, site) {
                    self.selectedSite = site;
                    self.site_uid = site_uid;
                    self.getCallStack.execute();
                });

                jQuery(widget.find('.site-selector')).html(this.siteSelector.render());
            },

            /**
            * Initialize tree
            */
            initializeTree: function () {
                var self = this,
                    uid = Math.random().toString(36).substr(2, 9),
                    config = {
                        dragAndDrop: true,
                        onCreateLi: this.onCreateLi,
                        id: this.config.popinId || 'bb-page-tree' + '-' + uid,
                        title: self.config.popin_title,
                        height: 400 > jQuery(window).height() - 40 ? jQuery(window).height() - 40 : 400,

                        onCanMove: function (node) {
                            if (node.is_fake || node.has_ellipsis) {
                                return false;
                            }
                            return true;
                        },

                        onCreate: function () {
                            if (self.hasSiteSelector()) {
                                self.handleSiteSelector(this.widget);
                            }
                        }
                    };

                this.formatedData = [];

                if (this.config.popin === true) {
                    this.tree = Tree.createPopinTreeView(config);
                    this.treeView = this.tree.treeView;
                    this.tree.on("click", ".show_folder_action", this.handleSectionFilter, this);
                    Core.ApplicationManager.invokeService('content.main.registerPopin', 'treeView', this.tree);
                } else {
                    this.treeView = this.tree = Tree.createTreeView(null, config);
                }

                this.bindDefaultEvents();
            },

            handleSectionFilter: function (e) {
                this.config.only_section = jQuery(e.target).is(":checked");
                this.previousTree = {};
                var leafSelector = "li.jqtree_common:not(.jqtree-folder)";
                if (this.config.only_section) {
                    this.saveTreeActions = true;
                    this.nodeActionMemory = [];
                    jQuery(this.treeView.treeEl).find(leafSelector).hide();
                } else {
                    this.saveTreeActions = false;
                    jQuery(this.treeView.treeEl).find(leafSelector).show();
                    this.handleNodeActionMemory();
                }
            },

            handleNodeActionMemory: function () {
                var memorizedNode,
                    self = this;

                jQuery.each(this.nodeActionMemory, function (i) {
                    memorizedNode = self.treeView.getNodeById(self.nodeActionMemory[i]);
                    if (memorizedNode) {
                        memorizedNode.before_load = true;
                       /* remove children too if they exist */
                        jQuery.each(memorizedNode.children, function (i) {
                            self.treeView.invoke("removeNode", memorizedNode.children[i]);
                        });

                        self.treeView.invoke("closeNode", memorizedNode);
                        self.treeView.invoke("openNode", memorizedNode);

                    }
                });
                this.nodeActionMemory = null;
            },

            /**
            * Event trigged when LI is created
            * @param {Object} node
            * @param {Object} li
            */
            onCreateLi: function (node, li) {

                var title = li.find('.jqtree-title');

                if (node.is_fake !== true) {
                    if (node.state === 0 || node.state === 2) {
                        title.html('<i class="bb5-ico-workflow offline"></i>' + title.html());
                    } else if (node.state === 3) {
                        title.html('<i class="bb5-ico-workflow masked"></i>' + title.html());
                    } else {
                        title.html('<i class="bb5-ico-workflow"></i>' + title.html());
                    }
                }
            },

            /**
            * Bind default events of tree
            */
            bindDefaultEvents: function () {
                this.treeView.on('tree.click', jQuery.proxy(this.onClick, this));
                this.treeView.on('tree.open', jQuery.proxy(this.onOpen, this));
            },

            /**
            * Event trigged on click in tree
            * @param {Object} event
            */
            onClick: function (event) {

                var self = this,
                    parent = event.node.parent;
                /* do nothing with ellipsis node */
                if (event.node.is_ellipsis) {
                    return;
                }

                if (event.node.is_fake === true && self.config.do_pagination === true) {
                    this.mask();
                    self.findPages(parent, parent.range_to + 1).done(function (data) {
                        self.treeView.invoke('removeNode', event.node);
                        self.insertDataInNode(data, parent);
                    }).always(function () {
                        self.unmask();
                    });
                }
            },

            /**
            * Event trigged on click in arrow in tree
            * @param {Object} event
            */
            onOpen: function (event) {
                var self = this;
                if (event.node.is_fake === true) {
                    return;
                }

                if (event.node.before_load === true) {
                    this.mask();
                    if (this.saveTreeActions) {
                        this.nodeActionMemory.push(event.node.id);
                    }

                    self.findPages(event.node, 0).done(function (data) {
                        self.insertDataInNode(data, event.node);
                        if (self.treeView.isRoot(event.node)) {
                            self.trigger("rootIsLoaded");
                        }
                        self.unmask();
                    });
                }
            },

            /**
            * Find pages with page repository and add limit in current node
            * @param {String} parent_uid
            * @param {Object} event
            * @param {Number} start
            * @param {Number} limit
            */
            findPages: function (node, start) {
                var dfd = jQuery.Deferred(),
                    self = this;

                PageRepository.findChildren(node.id, start, this.limit_of_page, this.config.only_section).done(function (data, response) {

                    self.updateLimit(node, response);

                    dfd.resolve(data);
                }).fail(function (e) {
                    dfd.reject(e);
                });

                return dfd.promise();
            },

            /**
            * Update limit of node for create pagination
            * @param {Object} event
            * @param {Object} response
            */
            updateLimit: function (node, response) {
                if (node !== undefined) {
                    node.range_total = response.getRangeTotal();
                    node.range_to = response.getRangeTo();
                    node.range_from = response.getRangeFrom();
                }
            },

            /**
            * Formate Page object to Node object
            * @param {Object} page
            * @returns {Object}
            */
            formatePageToNode: function (page) {
                page.children = [];

                page.id = page.uid;
                page.label = page.title;
                page.before_load = false;
                page.hasChildren = function () {
                    return this.children.length !== 0 || this.has_children === true;
                };

                if (page.has_children) {
                    if (this.config.do_loading === true) {
                        page.before_load = true;
                        page.children.push(this.buildNode(Translator.translate('loading'), {
                            'is_fake': true
                        }));
                    }
                }

                return page;
            },

            /**
            * Build a simple node
            * @param {String} label
            * @param {Object} properties
            * @returns {Object}
            */
            buildNode: function (label, properties) {
                var node = {};

                node.id = Math.random().toString(36).substr(2, 9);
                node.label = label;
                node.children = [];

                this.updateNode(node, properties);

                return node;
            },

            updateNode: function (node, properties) {
                var property;

                for (property in properties) {
                    if (properties.hasOwnProperty(property)) {
                        node[property] = properties[property];
                    }
                }
            },

            /**
            * Insert data in node, remove loader and add pagination if necessary
            * @param {Object} data
            * @param {Object} node
            */
            insertDataInNode: function (data, node) {
                var key, formattedNode, provNode, ellipsisNode = null, children = node.children;

                if (node.before_load) {
                    if (node.children.hasOwnProperty(0) && node.children[0].is_fake === true) {
                        this.treeView.invoke('removeNode', node.children[0]);
                    }
                    node.before_load = false;
                }

                for (key in data) {
                    if (data.hasOwnProperty(key)) {
                        formattedNode = this.formatePageToNode(data[key]);
                        ellipsisNode = this.handleEllipsisNode(formattedNode);
                        this.treeView.invoke('appendNode', formattedNode, node);

                        /* if an ellipsis node exists, move it at its real position */
                        if (ellipsisNode) {
                            provNode = this.treeView.getNodeById('prov_' + ellipsisNode.id);
                            this.treeView.invoke('moveNode', ellipsisNode, provNode, 'after');
                            this.treeView.invoke('removeNode', provNode);
                        }
                    }
                }

                if (node.range_total > children.length && this.config.do_pagination === true) {
                    this.treeView.invoke('appendNode', this.buildNode('next results...', {
                        'is_fake': true
                    }), node);
                }
            },


            selectPage: function (pageUid) {
                var self = this;
                if (!pageUid) { return false; }
                this.mask();
                PageRepository.findAncestors(pageUid).done(function (ancestorInfos) {

                    if (!Array.isArray(ancestorInfos) || ancestorInfos.length === 0) {
                        self.unmask();
                        return false;
                    }
                    var callbacks = [],
                        nodeCallBack,
                        parentNode,
                        index,
                        ancestor;

                    jQuery.each(ancestorInfos, function (i) {
                        ancestor = self.formatePageToNode(ancestorInfos[i]);
                        index = i - 1;
                        parentNode = (i === 0) ? null : ancestorInfos[index];
                        nodeCallBack = self.createNodeCallBack(ancestor, parentNode, i, callbacks, pageUid);
                        callbacks.push(nodeCallBack);
                    });

                    if (callbacks.length === 0) {
                        self.unmask();
                        return false;
                    }

                    callbacks[0].call(this);
                });

            },

            createNodeCallBack: function (ancestor, parentNode, nextId, callbacksList, pageUid) {
                var self = this,
                    nextCallback;
                return function () {
                    self.mask();
                    if ((callbacksList.length === 1) && (self.treeView.isRoot({id: ancestor.uid}))) {
                        self.handleLastNode(pageUid, ancestor);
                        return;
                    }

                    self.findPages(ancestor, 0).done(function (response) {

                        self.handleNewNode(ancestor, parentNode, response);

                        nextCallback = callbacksList[nextId + 1];
                        if (typeof callbacksList[nextId + 1] === "function") {
                            nextCallback.call(this);
                        } else {
                            self.handleLastNode(pageUid, ancestor);


                        }
                    });
                };
            },

            handleLastNode: function (pageUid, ancestor) {
                var self = this,
                    currentNode = this.treeView.getNodeById(pageUid);
                if (currentNode) {
                    this.treeView.invoke('selectNode', currentNode);
                    this.unmask();
                } else {
                    PageRepository.findCurrentPage().done(function (data) {
                        currentNode = self.formatePageToNode(data);
                        self.addEllipsisNode(currentNode, ancestor);
                        self.treeView.invoke('selectNode', self.treeView.getNodeById(currentNode.id));
                    }).always(function () {
                        self.unmask();
                    });
                }
            },

            handleNewNode: function (node, parentNode, nodeChildren) {
                /* case 1: The node is already in the tree: open it silently */
                var pageNode = this.treeView.getNodeById(node.uid),
                    rangeInfos = {};

                if (pageNode) {
                    /* as root is loaded by default add nothing to root */
                    if (!parentNode) { return; }
                    /* preserve range info */
                    rangeInfos.range_total = parentNode.range_total;
                    rangeInfos.range_to = parentNode.range_to;
                    rangeInfos.range_from = parentNode.range_from;
                    this.insertDataInNode(nodeChildren, jQuery.extend(pageNode, rangeInfos));
                } else {

                    /* case 2: the node hasn't been loaded yet */
                    this.addEllipsisNode(node, parentNode, nodeChildren);
                }
            },

            mask: function () {
                if (this.config.popin) {
                    this.tree.popIn.mask();
                }
            },

            unmask: function () {
                if (this.config.popin) {
                    this.tree.popIn.unmask();
                }
            },

            loadTreeRoot: function () {
                var root = this.treeView.invoke("getTree");
                if (root && root.children.length !== 0) {
                    this.treeView.invoke("openNode", root.children[0]);
                }
            },

            handleEllipsisNode: function (node) {
                var elpsNode = this.treeView.getNodeById("elps_" + node.uid),
                    linkedNode = this.treeView.getNodeById(node.uid);
                if (!elpsNode) {
                    return;
                }
                this.treeView.invoke("removeNode", elpsNode);
                if (!linkedNode) {
                    return;
                }
                linkedNode.has_ellipsis = false;
                node.id = "prov_" + node.uid;
                return linkedNode;
            },

            addEllipsisNode: function (node, parentNode, nodeChildren) {
                var ellipsis_before;
                if (parentNode) {
                    parentNode = this.treeView.getNodeById(parentNode.uid);
                }

                parentNode = parentNode || this.treeView.getRootNode();
                ellipsis_before = this.buildNode("...", {
                    is_fake: true,
                    is_ellipsis: true
                });

                ellipsis_before.id = "elps_" + node.uid;
                this.treeView.invoke("appendNode", ellipsis_before, parentNode);
                this.treeView.invoke("appendNode", node, parentNode);
                node = this.treeView.getNodeById(node.uid);
                node.has_ellipsis = true;
                if (Array.isArray(nodeChildren) && nodeChildren.length !== 0) {
                    this.insertDataInNode(nodeChildren, node);
                }
            },

            getTree: function () {
                var self = this,
                    dfd = jQuery.Deferred(),
                    rootNode;

                if (this.hasSiteSelector() && !this.siteSelectorIsReady) {
                    return this.getCallStack.append(this.getTree);
                }
                this.mask();
                PageRepository.findRoot(self.site_uid).done(function (data) {
                    self.isProcessing = true;
                    if (data.hasOwnProperty(0)) {
                        rootNode = data[0];
                        rootNode.id = rootNode.uid;
                        self.treeView.setData([self.formatePageToNode(rootNode)]);
                        dfd.resolve(self.tree);
                    }
                }).fail(function () {
                    dfd.reject();
                }).always(function () {
                    self.isProcessing = false;
                    self.unmask();
                });

                return dfd.promise();
            }
        });

        return PageViewTree;
    }
);
