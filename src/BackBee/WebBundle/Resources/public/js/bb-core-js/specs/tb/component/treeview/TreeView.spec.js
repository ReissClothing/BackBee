define(['tb.component/treeview/TreeView', 'jquery'], function (TreeviewMng, jQuery) {
    'use strict';
    describe("TestingTreeView", function () {
        var createFakeNode = function (label, id) {
                return {
                    label: label,
                    id: id
                };
            };
        it("Should create a Treeview", function () {
            var treeView = TreeviewMng.createTreeView();
            expect(treeView).toBeDefined();
        });
        it("Should render a Treeview", function () {
            var treeView, node, treeViewContainer = jQuery("<div class='container'/>").clone();
            jQuery("body").append(treeViewContainer);
            treeView = TreeviewMng.createTreeView();
            expect(jQuery(treeViewContainer).eq(0).children().length).toEqual(0);
            treeView.render(".container");
            expect(jQuery(treeViewContainer).length).not.toEqual(0);
            node = createFakeNode("root", 10);
            treeView.appendNode(node);
        });
        it("Should create a root Node", function () {
            var treeView = TreeviewMng.createTreeView(),
                node = createFakeNode("root", 10);
            treeView.appendNode(node);
            expect(treeView.isRoot(node)).toBe(true);
        });
    });
});