define(['tb.component/popin/PopIn'], function (PopIn) {
    'use strict';

    describe('PopIn specs', function () {

        var popin = new PopIn(),
            childPopin = new PopIn();

        it('PopIn initial state is closed', function () {
            expect(popin.isOpen()).toEqual(false);
            expect(popin.isClose()).toEqual(true);
            expect(popin.getChildren()).toEqual([]);
        });

        it('Test PopIn "id" property getter and setter', function () {
            expect(popin.getId()).toEqual(null);
            popin.setId('foo');
            expect(popin.getId()).toEqual('foo');
        });

        it('Test PopIn "options" property getter, setter and add functions', function () {
            expect(popin.getOptions()).toEqual({});
            popin.setOptions({bar: 'foo'});
            popin.addOption('foo', 'bar');
            expect(popin.getOptions()).toEqual({bar: 'foo', foo: 'bar'});
        });

        it('Test PopIn "title" property getter and setter', function () {
            expect(popin.getTitle()).toEqual('');
            popin.setTitle('foo');
            expect(popin.getTitle()).toEqual('foo');
        });

        it('Test PopIn "content" property getter and setter', function () {
            expect(popin.getContent()).toEqual('');
            popin.setContent('bar');
            expect(popin.getContent()).toEqual('bar');
        });

        it('Test PopIn "class" property getter and setter', function () {
            expect(popin.getClass()).toEqual('');
            popin.setClass('alert');
            expect(popin.getClass()).toEqual('alert');
        });

        it('Test PopIn "buttons" property setter', function () {
            var buttonCallback = function () {
                    return 'test';
                };

            expect(popin.getOptions().buttons).toEqual(undefined);
            popin.addButton('test', buttonCallback);
            popin.addButton('anotherTest', buttonCallback);
            expect(popin.getOptions().buttons).toEqual({
                'test': buttonCallback,
                'anotherTest': buttonCallback
            });
        });

        it('Test disable and enable of PopIn "modal" behavior', function () {
            expect(popin.isModal()).toEqual(false);
            popin.enableModal();
            expect(popin.isModal()).toEqual(true);
            popin.disableModal();
            expect(popin.isModal()).toEqual(false);
        });

        it('Test disable and enable of PopIn "resize" behavior', function () {
            expect(popin.isResizable()).toEqual(false);
            popin.enableResize();
            expect(popin.isResizable()).toEqual(true);
            popin.disableResize();
            expect(popin.isResizable()).toEqual(false);
        });

        it('Call PopIn::close() on a closed pop-in won\'t change pop-in state', function () {
            expect(popin.isClose()).toEqual(true);
            popin.close();
            expect(popin.isClose()).toEqual(true);
        });

        it('Call PopIn::open() on a closed pop-in will change its state to OPEN_STATE (= 1)', function () {
            expect(popin.isClose()).toEqual(true);
            popin.open();
            expect(popin.isOpen()).toEqual(true);
        });

        it('Call PopIn::open() on an opened pop-in won\'t change pop-in state', function () {
            expect(popin.isOpen()).toEqual(true);
            popin.open();
            expect(popin.isOpen()).toEqual(true);
        });


        it('Call PopIn::close() on a opened pop-in will change its state to CLOSE_STATE (= 0)', function () {
            expect(popin.isOpen()).toEqual(true);
            popin.close();
            expect(popin.isClose()).toEqual(true);
        });

        it('Call PopIn::destroy() on a pop-in will change its state to DESTROY_STATE (= 2) and unset every properties except to state', function () {
            var popIn = new PopIn();
            expect(popIn.isDestroy()).toEqual(false);
            popIn.destroy();
            expect(popIn.isDestroy()).toEqual(true);
            expect(popIn.id).toEqual(undefined);
            expect(popIn.content).toEqual(undefined);
            expect(popIn.options).toEqual(undefined);
            expect(popIn.children).toEqual(undefined);
        });

        it('Call PopIn::open() or PopIn::close() on a destroyed pop-in won\'t change pop-in state', function () {
            var popIn = new PopIn();
            popIn.destroy();
            expect(popIn.isDestroy()).toEqual(true);
            popIn.open();
            expect(popIn.isOpen()).toEqual(false);
            popIn.close();
            expect(popIn.isClose()).toEqual(false);
        });

        it('Add a non PopIn object as child of PopIn will raise an exception', function () {
            expect(popin.getChildren().length).toEqual(0);

            try {
                popin.addChild({});
                expect(true).toEqual(false);
            } catch (e) {
                expect(e).toEqual('PopIn::addChild only accept PopIn object which is not in destroy state.');
            }

            expect(popin.getChildren().length).toEqual(0);
        });

        it('Test of adding a PopIn object as child of PopIn', function () {
            expect(popin.getChildren().length).toEqual(0);

            popin.addChild(childPopin);

            expect(popin.getChildren().length).toEqual(1);
        });

        it('Cannot add a child pop-in if its has destroyed state or if the parent is destroyed', function () {
            var popIn = new PopIn(),
                destroyedPopIn = new PopIn();

            destroyedPopIn.destroy();
            try {
                popIn.addChild(destroyedPopIn);
                expect(true).toEqual(false);
            } catch (e) {
                expect(e).toEqual('PopIn::addChild only accept PopIn object which is not in destroy state.');
                expect(popIn.getChildren().length).toEqual(0);
            }

            try {
                destroyedPopIn.addChild(popIn);
                expect(true).toEqual(false);
            } catch (e) {
                expect(e).toEqual('PopIn::addChild only accept PopIn object which is not in destroy state.');
                expect(destroyedPopIn.getChildren()).toEqual(undefined);
            }
        });
    });
});
