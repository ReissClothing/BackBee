define(
    [
        'tb.component/formbuilder/form/element/views/form.element.view.hidden',
        'tb.component/formbuilder/form/ElementBuilder!Hidden',
        'text!tb.component/formbuilder/form/element/templates/hidden.twig'
    ],
    function (view, Constructor, template) {

        'use strict';

        describe('Testing ElementHidden', function () {

            var config = {
                    type: 'hidden',
                    placeholder: 'Foo',
                    value: 'Bar',
                    label: 'Jean pierre',
                    disabled: true
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
