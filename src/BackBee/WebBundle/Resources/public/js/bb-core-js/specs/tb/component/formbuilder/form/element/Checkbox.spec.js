define(
    [
        'tb.component/formbuilder/form/element/views/form.element.view.checkbox',
        'tb.component/formbuilder/form/ElementBuilder!Checkbox',
        'text!tb.component/formbuilder/form/element/templates/checkbox.twig'
    ],
    function (view, Constructor, template) {

        'use strict';

        describe('Testing ElementCheckbox', function () {

            var config = {
                    type: 'checkbox',
                    placeholder: 'Foo',
                    options: {foo: 'FOO', bar: 'BAR', foobar: 'FOOBAR'},
                    checked: ['foo', 'bar'],
                    inline: true,
                    label: 'My select'
                },
                formTag = 'hZ1e',
                element = new Constructor('list', config, formTag, view, template);

            it('Testing initialize', function () {

                expect(element.getOptions()).toEqual(config.options);
                expect(element.getValue()).toEqual(config.checked);
                expect(element.isInline()).toEqual(config.inline);
                expect(element.template.length).toBeGreaterThan(0);
            });

            it('Testing render', function () {

                expect(element.render().length).toBeGreaterThan(0);
            });
        });
    }
);
