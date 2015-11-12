define([
    'require',
    'Core/Renderer',
    'moment',
    'component!translator',
    'text!page/tpl/manage.item.list.twig'
], function (req, Renderer, moment, translator) {

    'use strict';

    var convertPageDate = function (page) {
            page.modified = moment(page.modified * 1000).format(translator.translate('date_format'));
        },

        convertPageState = function (page) {
            page.state = translator.translate('state-' + page.state_code);
        };

    /**
     * View of new page
     * @type {Object} Backbone.View
     */
    return {
        name: 'page',
        /**
         * Render the template into the DOM with the Renderer
         * @returns {Object} PageViewReview
         */
        render: function (renderMode, item) {
            convertPageDate(item);
            convertPageState(item);
            return Renderer.render(req('text!page/tpl/manage.item.' + renderMode + '.twig'), {page: item});
        }
    };
});