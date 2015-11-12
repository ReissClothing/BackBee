define(['component!popin', 'tb.component/popin/PopIn'], function (PopInManager, PopIn) {
    'use strict';

    describe('PopInManager specs', function () {

        it('Test creation of a pop-in', function () {
            var popIn = PopInManager.createPopIn();

            PopInManager.init({});
            PopInManager.init('body');

            expect(typeof popIn === 'object' && typeof popIn.isA === 'function' && popIn.isA(PopIn)).toEqual(true);
            expect(popIn.getId()).not.toEqual(null);
            expect(popIn.isClose()).toEqual(true);

            popIn = PopInManager.createPopIn({
                resizable: true,
                id: 'foobar'
            });

            expect(popIn.getId()).toEqual('foobar');
            expect(popIn.getOptions().resizable).toEqual(true);

            expect(typeof popIn.display).toEqual('function');
            spyOn(PopInManager, 'display');
            popIn.display();
            expect(PopInManager.display).toHaveBeenCalled();

            expect(typeof popIn.hide).toEqual('function');
            spyOn(PopInManager, 'hide');
            popIn.hide();
            expect(PopInManager.hide).toHaveBeenCalled();

            expect(typeof popIn.mask).toEqual('function');
            popIn.mask();

            expect(typeof popIn.unmask).toEqual('function');
            popIn.unmask();
        });

        it('Create a sub pop-in with invalid parent will raise an exception', function () {
            try {
                PopInManager.createSubPopIn();
                expect(true).toEqual(false);
            } catch (e) {
                expect(e).toEqual('Provided parent is not a PopIn object');
            }
        });

        it('Create a sub pop-in with a valid parent pop-in (sub pop-in will be part of parent pop-in children)', function () {
            var popIn = PopInManager.createPopIn(),
                childPopIn = PopInManager.createSubPopIn(popIn),
                parentPopInChildren = popIn.getChildren();

            expect(parentPopInChildren.length).toEqual(1);
            expect(parentPopInChildren[0]).toEqual(childPopIn);
        });

        it('Call PopInManager::display() on a closed pop-in will change its state to OPEN_STATE and execute its callback', function () {
            var popIn = PopInManager.createPopIn();

            expect(popIn.isClose()).toEqual(true);
            PopInManager.display(popIn);
            expect(popIn.isClose()).toEqual(false);
            expect(popIn.isOpen()).toEqual(true);
        });

        it('Call PopInManager::display() on already opened pop-in won\'t reexecute open process callback', function () {
            var popIn = PopInManager.createPopIn();

            PopInManager.display(popIn);

            spyOn(popIn, 'open');
            PopInManager.display(popIn);
            expect(popIn.open).not.toHaveBeenCalled();
        });

        it('Call PopInManager::hide() on an opened pop-in will change its state to CLOSE_STATE and execute its callback', function () {
            var popIn = PopInManager.createPopIn();

            PopInManager.display(popIn);
            expect(popIn.isOpen()).toEqual(true);
            PopInManager.hide(popIn);
            expect(popIn.isOpen()).toEqual(false);
            expect(popIn.isClose()).toEqual(true);
        });

        it('Call PopInManager::hide() on already closed pop-in won\'t reexecute close process callback', function () {
            var popIn = PopInManager.createPopIn();

            spyOn(popIn, 'close');
            PopInManager.hide(popIn);
            expect(popIn.close).not.toHaveBeenCalled();
        });

        it('Call PopInManager::hide() on a pop-in which has children will also close every opened child pop-in', function () {
            var popIn = PopInManager.createPopIn(),
                childPopIn = PopInManager.createSubPopIn(popIn);

            expect(popIn.isClose()).toEqual(true);
            PopInManager.display(popIn);
            expect(popIn.isOpen()).toEqual(true);

            expect(childPopIn.isClose()).toEqual(true);
            PopInManager.display(childPopIn);
            expect(childPopIn.isOpen()).toEqual(true);

            PopInManager.hide(popIn);
            expect(popIn.isClose()).toEqual(true);
            expect(childPopIn.isClose()).toEqual(true);
        });

        it('Call PopInManager::toggle() on a pop-in will display it its state is "hide" and will hide it if its state is "open"', function () {
            var popIn = PopInManager.createPopIn();

            expect(popIn.isClose()).toEqual(true);
            PopInManager.toggle(popIn);
            expect(popIn.isOpen()).toEqual(true);
            PopInManager.toggle(popIn);
            expect(popIn.isClose()).toEqual(true);
        });

        it('Call PopInManager::destroy() on an opened or closed pop-in will destroy it and its children', function () {
            var popIn = PopInManager.createPopIn(),
                childPopIn = PopInManager.createSubPopIn(popIn);

            PopInManager.display(popIn);
            expect(popIn.isDestroy()).toEqual(false);
            expect(childPopIn.isDestroy()).toEqual(false);
            PopInManager.destroy(popIn);
            expect(popIn.isDestroy()).toEqual(true);
            expect(childPopIn.isDestroy()).toEqual(true);
        });

        it('Call PopInManager::destroy() on an already destroyed pop-in won\'t reexecute destroy process', function () {
            var popIn = PopInManager.createPopIn();

            PopInManager.destroy(popIn);
            spyOn(popIn, 'destroy');
            PopInManager.destroy(popIn);
            expect(popIn.destroy).not.toHaveBeenCalled();
        });
    });
});
