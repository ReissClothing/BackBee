define(
    [
        'tb.component/formbuilder/form/Form',
        'text!tb.component/formbuilder/form/templates/form.twig',
        'tb.component/formbuilder/form/views/form.view',
        'tb.component/formbuilder/form/element/views/form.element.view.text',
        'tb.component/formbuilder/form/ElementBuilder!Text',
        'text!tb.component/formbuilder/form/element/templates/text.twig'
    ],
    function (Form) {

        'use strict';

        if (!Function.prototype.bind) {
            Function.prototype.bind = function (oThis) {
                if (typeof this !== "function") {
                    // closest thing possible to the ECMAScript 5 internal IsCallable function
                    throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
                }

                var aArgs = Array.prototype.slice.call(arguments, 1),
                    fToBind = this,
                    FNOP = function () {
                        return undefined;
                    },
                    fBound = function () {
                        return fToBind.apply(this instanceof FNOP && oThis ? this : oThis, aArgs.concat(Array.prototype.slice.call(arguments)));
                    };

                FNOP.prototype = this.prototype;
                fBound.prototype = new FNOP();

                return fBound;
            };
        }

        describe('Testing form', function () {

            var form,
                config = {
                    template: 'tb.component/formbuilder/form/templates/form.twig',
                    view: 'tb.component/formbuilder/form/views/form.view',
                    method: 'GET',
                    action: 'foo.php',
                    submitLabel: 'Save'
                };

            it('Testing config form errors (no config)', function () {
                try {
                    form = new Form();
                    expect(true).toBe(false);
                } catch (e) {
                    expect(e).toEqual('Error n 500 MissingConfigException: Config must be set');
                }
            });

            it('Testing config form errors (no template define)', function () {
                try {
                    form = new Form({});
                    expect(true).toBe(false);
                } catch (e) {
                    expect(e).toEqual('Error n 500 MissingPropertyException: Property "template" not found in form');
                }
            });

            it('Testing config form errors (no view define)', function () {
                try {
                    form = new Form({'template': 'my_template.twig'});
                    expect(true).toBe(false);
                } catch (e) {
                    expect(e).toEqual('Error n 500 MissingPropertyException: Property "view" not found in form');
                }
            });

            form = new Form(config);

            it('Testing config of form', function () {

                expect(form.getMethod()).toEqual(config.method);
                expect(form.getAction()).toEqual(config.action);
                expect(form.getSubmitLabel()).toEqual(config.submitLabel);
            });

            it('Testing CRUD of element', function () {

                var element = {
                    view: 'tb.component/formbuilder/form/element/views/form.element.view.text',
                    template: 'tb.component/formbuilder/form/element/templates/text.twig',
                    class: 'tb.component/formbuilder/form/ElementBuilder!Text',
                    type: 'text',
                    label: 'My name',
                    value: ''
                };

                expect(form.getElements()).toEqual({});

                form.add('name', element);

                expect(form.get('name')).toEqual(element);
                expect(form.get('foo')).toBe(null);

                form.remove('name');
                expect(form.get('name')).toBe(null);

            });
        });
    }
);
