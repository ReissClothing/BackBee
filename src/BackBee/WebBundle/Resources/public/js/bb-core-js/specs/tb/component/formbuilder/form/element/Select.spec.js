define(
    [
        'tb.component/formbuilder/form/element/views/form.element.view.select',
        'tb.component/formbuilder/form/ElementBuilder!Select',
        'text!tb.component/formbuilder/form/element/templates/select.twig'
    ],
    function (view, Constructor, template) {

        'use strict';

        describe('Testing ElementSelect', function () {

            var config = {
                    type: 'select',
                    placeholder: 'Foo',
                    options: {foo: 'FOO', bar: 'BAR', foobar: 'FOOBAR'},
                    selected: ['foo', 'bar'],
                    multiple: true,
                    label: 'My select'
                },
                formTag = 'hZ1e',
                element = new Constructor('list', config, formTag, view, template);

            it('Testing initialize', function () {

                expect(element.getOptions()).toEqual(config.options);
                expect(element.getValue()).toEqual(config.selected);
                expect(element.isMultiple()).toEqual(config.multiple);
                expect(element.template.length).toBeGreaterThan(0);
            });

            it('Testing render', function () {

                expect(element.render().length).toBeGreaterThan(0);
            });
        });
    }
);
