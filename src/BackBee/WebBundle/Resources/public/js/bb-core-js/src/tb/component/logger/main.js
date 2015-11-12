/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */
define('tb.component/logger/main', ['moment', 'Core', 'jsclass'], function (moment) {
    'use strict';

    /**
     * Logger is the base class for all BackBee toolbar logs
     */
    var LogLevels = {
            emergency: 1,
            alert: 2,
            critical: 3,
            error: 4,
            warning: 5,
            notice: 6,
            info: 7,
            debug: 8
        },

        Logger = new JS.Class({

            logs: [],

            initialize: function () {
                this.devmode = false;
                this.minimalLevel = 4;
                this.tmpConf = false;
            },

            init: function (config) {
                this.devmode = !(config.mode === 'production' || config.mode === undefined);
                this.minimalLevel = config.level || this.minimalLevel;
            },

            pushLog: function (log) {
                var Core = require('Core');

                if (undefined === Core.get('logs')) {
                    Core.set('logs', []);
                }

                Core.get('logs').push(log);
            },

            consoleLog: function (level, message) {
                if (level < 5) {
                    console.error(message);
                } else if (level === 5) {
                    console.warn(message);
                } else if (level === 6) {
                    console.log(message);
                } else if (level === 7) {
                    console.info(message);
                } else {
                    console.debug(message);
                }
            },

            buildLog: function (logLevel, level, message, context) {
                var log = {
                    level: logLevel,
                    time: moment(),
                    name: level,
                    message: message,
                    context: context
                };
                this.pushLog(log);
            },

            log: function (level, message, context) {
                var logLevel = 9;
                if (LogLevels.hasOwnProperty(level)) {
                    logLevel = LogLevels[level];
                } else if (!isNaN(parseInt(level, 10))) {
                    logLevel = parseInt(level, 10);
                }
                if ((this.devmode || (this.tmpConf && this.tmpConf.devmode)) && console) {
                    this.consoleLog(logLevel, message);
                }

                if (logLevel <= this.minimalLevel || (this.tmpConf && this.tmpConf.level <= logLevel)) {
                    this.buildLog(logLevel, level, message, context);
                }
            },

            updateLogLevel: function (minLevel, mode) {
                if (minLevel && !isNaN(parseInt(minLevel, 10))) {
                    this.tmpConf = {
                        level: minLevel,
                        devmode: !(mode === 'production' || mode === undefined)
                    };
                }
            },

            restaureLogLevel: function () {
                this.tmpConf = false;
            }
        }),

        logger = new Logger();


    /**
     * Describes the logger instance
     *
     * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
     * for the full interface specification.
     */
    return {
        /**
         * System is unusable.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        emergency: function (message, context) {
            logger.log('emergency', message, context);
        },

        /**
         * Action must be taken immediately.
         *
         * Example: Entire website down, database unavailable, etc. This should
         * trigger the SMS alerts and wake you up.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        alert: function (message, context) {
            logger.log('alert', message, context);
        },

        /**
         * Critical conditions.
         *
         * Example: Application component unavailable, unexpected exception.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        critical: function (message, context) {
            logger.log('critical', message, context);
        },

        /**
         * Runtime errors that do not require immediate action but should typically
         * be logged and monitored.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        error: function (message, context) {
            logger.log('error', message, context);
        },

        /**
         * Exceptional occurrences that are not errors.
         *
         * Example: Use of deprecated APIs, poor use of an API, undesirable things
         * that are not necessarily wrong.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        warning: function (message, context) {
            logger.log('warning', message, context);
        },

        /**
         * Normal but significant events.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        notice: function (message, context) {
            logger.log('notice', message, context);
        },

        /**
         * Interesting events.
         *
         * Example: User logs in, SQL logs.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        info: function (message, context) {
            logger.log('info', message, context);
        },

        /**
         * Detailed debug information.
         *
         * @param string $message
         * @param array $context
         * @return null
         */
        debug: function (message, context) {
            logger.log('debug', message, context);
        },

        /**
         * Logs with an arbitrary level.
         *
         * @param mixed $level
         * @param string $message
         * @param array $context
         * @return null
         */
        log: function (level, message, context) {
            logger.log(level, message, context);
        },

        /**
         * Update temporaly the log level
         * @param  Number minLevel
         * @param  string mode
         */
        updateLogLevel: function (minLevel, mode) {
            logger.updateLogLevel(minLevel, mode);
        },

        /**
         * Restaure the application original log level
         */
        restaureLogLevel: function () {
            logger.restaureLogLevel();
        },

        init: function (config) {
            logger.init(config);
            delete this.init;
        }
    };
});
