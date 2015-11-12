/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


define('jquery-helper', ['jquery'], function (coreJQuery) {
    'use strict';
    return {
        restoreCoreJQuery: function () {
            if (window.jQuery) {
                window.jQuery = coreJQuery;
                window.$ = coreJQuery;
            }
        },

        restoreClientJQuery: function () {
            coreJQuery.noConflict(true);
        }
    };
});