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

define('tb.component/formbuilder/form/element/Content', function () {

    'use strict';

    /**
     * ElementContent object
     */
    return {

        initialize: function (key, config, formTag, view, template, error) {
            this.callSuper(key, config, formTag, error);
            this.view = view;
            this.template = template;

            this.buildCustomConfig(config);

            this.viewObject = new this.view(this.template, this.formTag, this);
        },

        buildCustomConfig: function (config) {
            this.image = config.image;
            this.title = config.object_label;
            this.classname = config.object_type;
            this.popinInstance = config.popinInstance;
        },

        render: function () {
            return this.viewObject.render();
        }
    };
});
