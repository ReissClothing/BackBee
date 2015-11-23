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
 */

namespace BackBee\WebBundle\Renderer\Helper;

/**
 *
 *
 * @category    BackBee
 * @package     BackBee\Bundle\ToolbarBundle
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class bbtoolbar extends AbstractHelper
{
    /**
     * @var
     */
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }
    /**
     *
     *
     * @return
     */
    public function __invoke()
    {

        $wrapper = (isset($this->settings['wrapper_toolbar_id'])) ? $this->settings['wrapper_toolbar_id'] : '';

        /* add option bo connect */
        //        @TODO gvf
//        $config = $this->getRenderer()->getApplication()->getContainer()->get('bundle.toolbar.config')->getBundleConfig();
//        $disableToolbar = (isset($config['disable_toolbar'])) ? $config['disable_toolbar'] : false;
        $disableToolbar = false;
        
        return $this->getRenderer()->partial('partials/bbtoolbar.twig', array('wrapper' => $wrapper, 'disableToolbar' => $disableToolbar));
    }
}
