<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
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
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\WebBundle\Renderer\Helper;

/**
 * @category    BackBee
 *
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class bundleAdminForm extends bundleAdminUrl
{

    /**
     * @param  string   $route      route is composed by the bundle, controller and action name separated by a dot
     * @param  array    $query      optional url parameters and query parameters
     * @param  string   $httpMethod http method
     *
     * @return string               url
     */
    public function __invoke($route, Array $query = [], $httpMethod = 'POST')
    {
        $url = parent::__invoke($route, $query);

        return 'data-bundle="form" action="'.$url.'" data-http-method="'.$this->getJsMethod($httpMethod).'" method="'.$httpMethod.'"';
    }
}
