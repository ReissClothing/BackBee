define(
    [
        'tb.component/formbuilder/form/element/views/form.element.view.password',
        'tb.component/formbuilder/form/ElementBuilder!Password',
        'text!tb.component/formbuilder/form/element/templates/password.twig'
    ],
    function (view, Constructor, template) {

        'use strict';

        describe('Testing ElementPassword', function () {

            var config = {
                    type: 'password',
                    label: 'My password',
                    value: '123456789',
                    disabled: true
                },
                formTag = 'hZ1e',
                element = new Constructor('my_password', config, formTag, view, template);

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
