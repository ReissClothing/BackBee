define(['require', 'Core', 'component!logger'], function (require) {
    'use strict';

    var api = require('Core'),
        logger = require('component!logger');

    describe('Logger spec', function () {

        it('Logs actions', function () {
            api.set('logs', []);

            logger.emergency('Test a emergency log.');

            expect(api.get('logs').length).toBe(1);

            logger.info('Test a info log.');
            expect(api.get('logs').length).toBe(1);
        });

        it('Change logs level', function () {

            logger.updateLogLevel(6);
            logger.notice('Test a notice log with updated log level.');
            expect(api.get('logs').length).toBe(2);

            logger.restaureLogLevel();
            logger.warning('Test a warning log with reset log level.');
            expect(api.get('logs').length).toBe(2);

            logger.updateLogLevel();
            logger.debug('Test is a debug log with unvailable updated log level.');
            expect(api.get('logs').length).toBe(2);
        });

        it('Try custom log level', function () {

            logger.log(2, 'Test a custom log level 2.');
            expect(api.get('logs').length).toBe(3);

            logger.log('information', 'Test a custom log level 9.');
            expect(api.get('logs').length).toBe(3);
        });

        it('Try all miscellious log levels', function () {
            spyOn(console, 'error');
            spyOn(console, 'warn');
            spyOn(console, 'log');
            spyOn(console, 'info');
            spyOn(console, 'debug');

            logger.updateLogLevel(9, 'spec');

            logger.emergency('Test is a alert log.');
            expect(console.error).toHaveBeenCalled();

            logger.alert('Test is a alert log.');
            expect(console.error).toHaveBeenCalled();

            logger.critical('Test is a alert log.');
            expect(console.error).toHaveBeenCalled();

            logger.error('Test is a alert log.');
            expect(console.error).toHaveBeenCalled();

            logger.warning('Test is a alert log.');
            expect(console.warn).toHaveBeenCalled();

            logger.notice('Test is a alert log.');
           // expect(console.log).toHaveBeenCalled();

            logger.info('Test is a alert log.');
            expect(console.info).toHaveBeenCalled();

            logger.debug('Test is a alert log.');
            expect(console.debug).toHaveBeenCalled();

            logger.log('spec', 'Test is a alert log.');
            expect(console.debug).toHaveBeenCalled();

        });
    });
});