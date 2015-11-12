/*global $:false */
define(['jquery', 'jquery-layout'], function (jQuery) {
    'use strict';
    if (!jQuery.fn.hasOwnProperty('layout')) {
        jQuery.fn.layout = $.fn.layout;
    }
});