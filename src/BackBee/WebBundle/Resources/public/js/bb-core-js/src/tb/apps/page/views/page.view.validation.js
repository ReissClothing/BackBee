define(
    ['text!page/tpl/validation.twig', 'jquery', 'component!popin', 'Core/Renderer'],
    function (tpl, jQuery, PopinManager, Renderer) {
        'use strict';
        var popin;
        return Backbone.View.extend({
            popin_config: {
                id: 'bb-validation-popin',
                width: 250,
                top: 180,
                close: function () {
                    PopinManager.destroy(popin);
                }
            },

            bindAction: function () {
                var self = this;
                jQuery('#bb-validate-button').click(function () {
                    self.dfd.resolve();
                    self.destruct();
                });
                jQuery('#bb-cancel-button').click(function () {
                    self.dfd.reject();
                });
            },

            /**
             * Initialize of PageViewEdit
             */
            initialize: function (data) {
                if (data.popin) {
                    popin = PopinManager.createSubPopIn(data.popin, this.popin_config);
                } else {
                    popin = PopinManager.createPopIn(this.popin_config);
                }
                popin.setContent(Renderer.render(tpl, {text: data.text}));
            },


            display: function () {
                this.dfd = jQuery.Deferred();

                popin.display();
                this.bindAction();
                return this.dfd.promise();
            },

            destruct: function () {
                PopinManager.destroy(popin);
            }
        });
    }
);