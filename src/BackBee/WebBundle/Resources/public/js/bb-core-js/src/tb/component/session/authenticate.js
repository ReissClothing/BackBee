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
define('tb.component/session/authenticate', ['tb.component/session/session'], function (Session) {
    'use strict';

    return {
        load: function () {
            var args = Array.prototype.slice.call(arguments),
                req = args[1],
                onload = args[2];

            if (Session.isAuthenticated()) {
                onload(Session);
            } else {
                req(['component!authentication'], function (authentication) {
                    authentication.showForm();
                });
                onload(Session);
            }
        }
    };
});