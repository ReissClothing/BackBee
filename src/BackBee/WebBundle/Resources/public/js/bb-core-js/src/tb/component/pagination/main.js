/*global jQuery:false, $:false, Backbone:false */
/*jshint -W004 */
define(['jquery', 'jssimplepagination'], function (corejQuery) {
    'use strict';
    /*make sure our jQuery instance has the extension*/
    if (!corejQuery.fn.hasOwnProperty("pagination")) {
        if ($) {
            corejQuery.fn.pagination = $.fn.pagination;
        }
        if (jQuery) {
            corejQuery.fn.pagination = jQuery.fn.pagination;
        }
    }
    var $ = corejQuery,
        Pagination = new JS.Class({

            defaultConfig: {
                css: {},
                cls: 'pagination clearfix',
                items: 0,
                itemsOnPage: 10,
                displayedPages: 5,
                prevText: 'Prev',
                nextText: 'Next',
                theme: 'tpl-name'
            },

            initialize: function (config) {
                this.config = $.extend({}, this.defaultConfig, config);
                this.IS_VISIBLE = false;
                this.silenceNextEvent = false;
                $.extend(this, {}, Backbone.Events);
                this.widget = $("<div/>").clone();
                this.bindEvents();
                this.build();
                this.setItemsOnPage(this.config.itemsOnPage, true);
            },

            setItems: function (nb) {
                this.invoke('updateItems', parseInt(nb, 10));
                this.beforeRender(this.widget);
            },

            setItemsOnPage: function (nb, silent) {
                silent = (typeof silent === 'boolean') ? silent : false;
                this.silenceNextEvent = silent;
                this.invoke('updateItemsOnPage', nb);
                this.beforeRender(this.widget);
            },

            bindEvents: function () {
                var self = this;
                this.widget.on('click', '.first-btn', function () {
                    self.selectPage(1);
                });
                this.widget.on('click', '.last-btn', function () {
                    var conf = self.getPaginationConf();
                    if (conf) {
                        self.selectPage(conf.pages);
                    }
                });
                this.widget.on('click', '.bb5-pagination-next', function () {
                    self.nextPage();
                });
                this.widget.on('click', '.bb5-pagination-prev', function () {
                    self.prevPage();
                });
            },

            checkState: function () {
                var hideWidget = false,
                    state = this.getPaginationConf();
                if (parseInt(state.items, 10) === 0 || parseInt(state.items, 10) <= parseInt(state.itemsOnPage, 10)) {
                    hideWidget = true;
                }
                if (hideWidget) {
                    $(this.widget).hide();
                    this.IS_VISIBLE = false;
                } else {
                    $(this.widget).show();
                    this.IS_VISIBLE = true;
                }
                this.trigger('afterRender', this.IS_VISIBLE);
            },

            getPaginationConf: function () {
                return $(this.widget).data('pagination');
            },

            reset: function () {
                this.invoke('redraw');
                this.beforeRender(this.widget);
            },

            build: function () {
                $(this.widget).css(this.defaultConfig.css);
                this.config.onPageClick = $.proxy(this.handlePageClick, this);
                $(this.widget).pagination(this.config);
            },

            handlePageClick: function (currentPage) {
                if (this.silenceNextEvent) {
                    this.silenceNextEvent = false;
                    return;
                }
                this.beforeRender(this.widget);
                this.trigger('pageChange', currentPage);
            },

            /* We provide a programatic way to adapt the render view */
            beforeRender: function (widget) {
                var mainContainer = $(widget).find('ul'),
                    firstBtn = mainContainer.find('.first-btn'),
                    prevCurrent = mainContainer.find('.page-link.prev').eq(0),
                    nextCurrent = mainContainer.find('.next').eq(0),
                    lastBtn = mainContainer.find('.last-btn');
                $(mainContainer).addClass(this.defaultConfig.cls);
                /* append first && last if needed */
                if (!firstBtn.length) {
                    $(mainContainer).prepend($('<li><a class="bb5-pagination-btn first-btn" href="#"><i class="fa fa-angle-double-left"></i></a></li>'));
                }
                if (!lastBtn.length) {
                    $(mainContainer).append($('<li><a class="bb5-pagination-btn last-btn" href="#"><i class="fa fa-angle-double-right"></i></a></li>'));
                }
                /*handle current prev*/
                if (!prevCurrent.length) {
                    prevCurrent = mainContainer.find('.current.prev').eq(0);
                }
                prevCurrent.parent().append('<a class="current prev bb5-pagination-btn bb5-pagination-prev" href="javascript:;"><i class="fa fa-angle-left"></i></a>');
                prevCurrent.remove();
                /*handle next */
                nextCurrent.parent().append('<a class="page-link next bb5-pagination-btn bb5-pagination-next" href="javascript:;"> <i class="fa fa-angle-right"></i></a>');
                nextCurrent.remove();
                mainContainer.find('.current').eq(1).addClass('bb5-pagination-current').parent().removeClass('active');
                mainContainer.find('.disabled').removeClass('disabled');
                this.checkState();
                return widget;
            },

            invoke: function (methodName) {
                var args = Array.prototype.slice.call(arguments, 0);
                args.shift();
                try {
                    this.widget.pagination(methodName, args.join(', '));
                } catch (e) {
                    throw "PaginationException Error while invoking " + methodName + e;
                }
            },

            render: function (container, positionMethod) {
                if (typeof this.beforeRender === "function") {
                    this.widget = this.beforeRender(this.widget);
                }
                positionMethod = (typeof positionMethod === "string") ? positionMethod : 'html';
                if (container && corejQuery(container).length) {
                    corejQuery(container)[positionMethod](this.widget);
                } else {
                    return this.widget;
                }
            },

            selectPage: function (pageNo, silent) {
                silent = (typeof silent === 'boolean') ? silent : false;
                this.silenceNextEvent = silent;
                this.invoke('selectPage', pageNo);
            },

            prevPage: function () {
                this.invoke('prevPage');
            },

            nextPage: function () {
                this.invoke('nextPage');
            },

            getPagesCount: function () {
                return this.invoke('getPagesCount');
            },

            getCurrentPage: function () {
                return this.invoke('getCurrentPage');
            },

            destroy: function () {
                this.invoke('destroy');
            },
            disable: function () {
                this.invoke('disable');
            },

            enable: function () {
                this.invoke('enable');
            },

            getItemsOnPage: function () {
                var conf = this.getPaginationConf();
                return conf.itemsOnPage;
            }
        });
    return {
        createPagination: function (config) {
            config = config || {};
            return new Pagination(config);
        },
        Pagination: Pagination
    };
});