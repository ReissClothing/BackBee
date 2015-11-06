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

use BackBee\Bundle\BundleControllerResolver;
use BackBee\Bundle\Exception\BundleConfigurationException;

/**
 * @category    BackBee
 *
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class bundleAdminUrl extends AbstractHelper
{

    /**
     * @param  string   $route      route is composed by the bundle, controller and action name separated by a dot
     * @param  array    $query      optional url parameters and query parameters
     *
     * @return string               url
     */
    public function __invoke($route, Array $query = [])
    {
        $route = explode('.', $route);

        if (count($route) != 3) {
            throw new BundleConfigurationException('Route definition is not well formated '.implode('.', $route), BundleConfigurationException::ADMIN_ROUTE_BADLY_INVOKED);
        }

        list($bundle, $controller, $action) = $route;

        $application = $this->_renderer->getApplication();

        if ($application->isDebugMode()) {
            $this->checkParameters($bundle, $controller, $action);
        }

        $url = (new BundleControllerResolver($application))->resolveBaseAdminUrl($bundle, $controller, $action);

        $url .= $this->parseQueryParameters($bundle, $controller, $action, $query);

        return $url;
    }

    /**
     * parse query parameters to build the end of the url
     *
     * @param  String $bundle
     * @param  String $controller
     * @param  String $action
     * @param  Array  $query
     *
     * @return String
     */
    private function parseQueryParameters($bundle, $controller, $action, $query)
    {
        $url = '';

        if (0 !== count($query)) {
            $controller = (new BundleControllerResolver($this->_renderer->getApplication()))->resolve($bundle, $controller);

            $methodParameters = (new \ReflectionMethod($controller, $action.'Action'))->getParameters();

            $parameters = [];

            foreach ($methodParameters as $value) {
                if (!in_array($value->name, $query)) {
                    $parameters[$value->name] = $query[$value->name];
                    unset($query[$value->name]);
                }
            }

            if (count($parameters) !== 0) {
                $url .= '/'.implode('/', $parameters);
            }

            if (count($query) !== 0) {
                foreach ($query as $key => $value) {
                    $query[$key] = $key.'='.$value;
                }
                $url .= $url.'?'.implode('&', $query);
            }
        }

        return $url;
    }

    /**
     * Do the correspondence behind js method and http methods
     *
     * @param  String $method
     * @return String
     */
    protected function getJsMethod($method)
    {
        $methods = [
            'get' => 'read',
            'post' => 'create',
            'put' => 'update',
            'delete' => 'delete',
        ];
        return isset($methods[strtolower($method)]) ? $methods[strtolower($method)] : 'read';
    }

    /**
     * Check if each parameters are correctly setted
     *
     * @param  string $bundle       bundle name
     * @param  string $controller   controller name
     * @param  string $action       action name
     *
     * @throws Exception            Bad configuration
     */
    private function checkParameters($bundle, $controller, $action)
    {
        $application = $this->_renderer->getApplication();

        $bundleController = (new BundleControllerResolver($application))->resolve($bundle, $controller);

        if (!method_exists($bundleController, $action.'Action')) {
            throw new \BadMethodCallException($bundleController.' doesn\'t have '.$action.'Action method', 1);
        }
    }
}
