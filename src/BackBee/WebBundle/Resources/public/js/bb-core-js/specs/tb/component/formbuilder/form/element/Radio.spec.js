define(
    [
        'tb.component/formbuilder/form/element/views/form.element.view.radio',
        'tb.component/formbuilder/form/ElementBuilder!Radio',
        'text!tb.component/formbuilder/form/element/templates/radio.twig'
    ],
    function (view, Constructor, template) {

        'use strict';

        describe('Testing ElementRadio', function () {

            var config = {
                    type: 'radio',
                    options: {foo: 'FOO', bar: 'BAR', foobar: 'FOOBAR'},
                    checked: ['foo', 'bar'],
                    inline: true,
                    label: 'My radio'
                },
                formTag = 'hZ1e',
                element = new Constructor('radio_name', config, formTag, view, template);

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
