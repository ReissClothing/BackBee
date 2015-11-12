define(['tb.component/formbuilder/form/element/Element'], function (ElementConstructor) {
    'use strict';

    describe('Testing FormElement', function () {

        var formTag = '1Zvze';

        it('Testing FormElement constructor error (Missing key or config)', function () {
            var element;

            try {
                element = new ElementConstructor();
                expect(false).toBe(true);
            } catch (e) {
                expect(e).toEqual('Error n 500 BadTypeException: The key of element must be a string');
            }

            try {
                element = new ElementConstructor('foo');
                expect(false).toBe(true);
            } catch (e) {
                expect(e).toEqual('Error n 500 MissingConfigException: Config must be set');
            }

            try {
                element = new ElementConstructor('foo', {});
                expect(false).toBe(true);
            } catch (e) {
                expect(e).toEqual('Error n 500 BadTypeException: The formTag of element must be a string');
            }

            expect(element).not.toBeDefined();
        });

        it('Testing FormElement constructor error (Missing type)', function () {
            var config = {
                    label: 'My label'
                },
                element;

            try {
                element = new ElementConstructor('foo', config, formTag);
                expect(false).toBe(true);
            } catch (e) {
                expect(e).toEqual('Error n 500 MissingPropertyException: Property "type" not found of element: foo');
            }

            expect(element).not.toBeDefined();
        });

        it('Testing FormElement constructor lite', function () {
            var config = {
                    type: 'text'
                },
                element = new ElementConstructor('name', config, formTag);

            expect(element.getKey()).toEqual('name');
            expect(element.getConfig()).toEqual(config);
            expect(element.getPlaceholder()).toEqual('');
            expect(element.getValue()).toEqual('');
            expect(element.getLabel()).toEqual('');
            expect(element.isDisabled()).toEqual(false);
            expect(element.getType()).toEqual('text');
        });

        it('Testing FormElement constructor full', function () {
            var config = {
                    type: 'text',
                    placeholder: 'Foo',
                    value: 'Bar',
                    label: 'Jean pierre',
                    disabled: true
                },
                element = new ElementConstructor('name', config, formTag);

            expect(element.getKey()).toEqual('name');
            expect(element.getConfig()).toEqual(config);
            expect(element.getPlaceholder()).toEqual('Foo');
            expect(element.getValue()).toEqual('Bar');
            expect(element.getLabel()).toEqual('Jean pierre');
            expect(element.isDisabled()).toEqual(true);
            expect(element.getType()).toEqual('text');
        });
    });
});
