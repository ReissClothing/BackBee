define(['Core', 'jquery'], function (Core, jQuery) {
    'use strict';
    var formaterMap = {
        category: 'formatCategory',
        contenttype: 'formatContentType'
    },
        NodeFormater = {
            formatSubcontents: function (contents) {
                var result = [],
                    data = contents || [],
                    cContent;
                jQuery.each(data, function (i) {
                    cContent = data[i];
                    if (!cContent.visible) {
                        return true;
                    }
                    if (cContent.hasOwnProperty("label") && typeof cContent.label === "string") {
                        cContent.isACategory = false;
                        cContent.no = i + 1;
                        result.push(cContent);
                    }
                });
                return result;
            },

            format: function (type, data) {
                var formater = formaterMap[type],
                    contents = data || {};
                if (typeof this[formater] !== "function") {
                    Core.exception('NodeFormaterException', 12201, 'format: formater ' + formater + 'doesn\'t exist');
                }
                return this[formater](contents);
            },

            formatCategory: function (data) {
                var self = this,
                    contents = data || {},
                    root = {
                        label: "Category",
                        children: [],
                        isRoot: true
                    },
                    result = [];
                jQuery.each(contents, function (i, category) {
                    if (!self.isCategoryVisible(category)) {
                        return true;
                    }
                    category.label = category.name;
                    category.isACategory = true;
                    category.no = i + 1;
                    category.children = self.formatSubcontents(category.contents);
                    root.children.push(category);
                });
                result.push(root);
                return result;
            },

            isCategoryVisible: function (category) {
                var isVisible = false,
                    content;
                if (category.contents) {
                    jQuery.each(category.contents, function (i) {
                        content = category.contents[i];
                        if (content.visible) {
                            isVisible = true;
                        }
                        return false;
                    });
                }
                return isVisible;
            },

            formatContentType: function (data) {
                var result = [],
                    root = {
                        label: "Contents",
                        children: [],
                        isRoot: true
                    };
                jQuery.each(data, function (i, content) {
                    root.children.push({
                        label: content,
                        type: content,
                        no: i + 1,
                        isACategory: false
                    });
                });
                result.push(root);
                return result;
            }
        };
    return {
        format: jQuery.proxy(NodeFormater.format, NodeFormater)
    };
});