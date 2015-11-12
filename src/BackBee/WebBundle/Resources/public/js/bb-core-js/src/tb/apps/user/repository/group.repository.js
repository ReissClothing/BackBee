
/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

define(
    ['user/repository/abstract.rest.repository', 'jsclass'],
    function (AbstractRestRepository) {
        'use strict';

        /**
         * Page repository class
         * @type {Object} JS.Class
         */
        var GroupRepository = new JS.Class(AbstractRestRepository, {

            /**
             * Initialize of Page repository
             */
            initialize: function () {
                this.class_name = 'group';
                this.mandatories_attribute = ['name'];
                this.identifier = 'id';

                this.initializeRestDriver();
            }

        });

        return new JS.Singleton(GroupRepository);
    }
);
