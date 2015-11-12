define(
    [
        'tb.component/formbuilder/form/element/views/form.element.view.text',
        'tb.component/formbuilder/form/ElementBuilder!Text',
        'text!tb.component/formbuilder/form/element/templates/text.twig'
    ],
    function (view, Constructor, template) {

        'use strict';

        describe('Testing ElementDatetimepicker', function () {

            var config = {
                    type: 'datetimepicker',
                    value: '1991-21-03',
                    label: 'My datetimepicker'
                },
                formTag = 'hZ1e',
                element = new Constructor('name', config, formTag, view, template);

            it('Testing initialize', function () {

                expect(element.template.length).toBeGreaterThan(0);
                expect(element.getValue()).toEqual(config.value);
            });

            it('Testing render', function () {

                expect(element.render().length).toBeGreaterThan(0);
            });
        });
    }
);
