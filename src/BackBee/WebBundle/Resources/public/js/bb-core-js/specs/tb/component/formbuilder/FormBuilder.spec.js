define(['component!formbuilder'], function (FormBuilder) {
    'use strict';


    describe('Testing FormBuilder', function () {
        it('Testing load method', function () {

            var config = {
                    elements: {
                        name: {
                            type: 'text',
                            label: 'My name',
                            value: ''
                        },
                        lastname: {
                            type: 'text',
                            label: 'My last name',
                            value: ''
                        }
                    }
                };

            try {
                FormBuilder.renderForm({});
                expect(false).toBe(true);
            } catch (e) {
                expect(e).toEqual('Error n 500 MissingPropertyException: Property "elements" not found');
            }

            try {
                FormBuilder.renderForm(config);
                expect(true).toBe(true);
            } catch (e) {
                expect(true).toBe(false);
            }
        });
    });
});
