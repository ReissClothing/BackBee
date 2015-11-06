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
     *
     *
     * @return
     */
    public function __invoke()
    {
//        @TODO gvf hardcodeado x mi
$settings = array (
    'core' =>
        array (
            'ApplicationManager' =>
                array (
                    'appPath' => '$resources-baseurl$/toolbar/src/tb/apps',
                    'active' => 'main',
                    'route' => '',
                    'applications' =>
                        array (
                            'main' =>
                                array (
                                    'label' => 'Main',
                                    'config' =>
                                        array (
                                            'mainRoute' => 'appMain/index',
                                        ),
                                ),
                            'content' =>
                                array (
                                    'label' => 'Edition du contenu',
                                    'config' =>
                                        array (
                                        ),
                                ),
                            'bundle' =>
                                array (
                                    'label' => 'Bundle',
                                    'config' =>
                                        array (
                                            'mainRoute' => 'bundle/index',
                                        ),
                                ),
                            'page' =>
                                array (
                                    'label' => 'Page',
                                    'config' =>
                                        array (
                                            'mainRoute' => 'page/index',
                                        ),
                                ),
                            'contribution' =>
                                array (
                                    'label' => 'Contribution',
                                    'config' =>
                                        array (
                                            'mainRoute' => 'contribution/index',
                                        ),
                                ),
                            'user' =>
                                array (
                                    'label' => 'User',
                                    'config' =>
                                        array (
                                            'mainRoute' => 'user/index',
                                        ),
                                    'scope' =>
                                        array (
                                            'global' =>
                                                array (
                                                    'open' => 'user.showCurrent',
                                                ),
                                        ),
                                ),
                        ),
                ),
        ),
    'wrapper_toolbar_id' => 'bb5-ui',
    'default_url' => 'content/contribution/edit',
    'component' =>
        array (
            'logger' =>
                array (
                    'level' => 8,
                    'mode' => 'devel',
                ),
            'exceptions-viewer' =>
                array (
                    'show' => true,
                    'showInConsole' => true,
                ),
            'medialibrary' =>
                array (
                    'available_media' =>
                        array (
                            0 =>
                                array (
                                    'title' => 'Image',
                                    'type' => 'Media/Image',
                                    'ico' => 'fa fa-picture',
                                ),
                            1 =>
                                array (
                                    'title' => 'Pdf',
                                    'type' => 'Media/Pdf',
                                    'ico' => 'fa fa-file-pdf-o',
                                ),
                        ),
                ),
            'translator' =>
                array (
                    'base' => '$resources-baseurl$/toolbar/src/tb/i18n/',
                    'default_locale' => 'en_US',
                    'locales' =>
                        array (
                            'en_US' => 'EN',
                            'fr_FR' => 'FR',
                            'ru_RU' => 'RU',
                        ),
                ),
        ),
    'unclickable_contents' =>
        array (
            'contents' =>
                array (
                    0 => 'Element/Text',
                    1 => 'Element/Attachment',
                    2 => 'Element/Date',
                    3 => 'Element/File',
                    4 => 'Element/Image',
                    5 => 'Element/Keyword',
                    6 => 'Element/Link',
                    7 => 'Element/Select',
                ),
        ),
);
//        @TODO gvf
//        $settings = $this->getRenderer()
//                         ->getApplication()
//                         ->getContainer()
//                         ->get('bundle.toolbar.config')
//                         ->getSection('settings');
        
        $wrapper = (isset($settings['wrapper_toolbar_id'])) ? $settings['wrapper_toolbar_id'] : '';

        /* add option bo connect */
        //        @TODO gvf
//        $config = $this->getRenderer()->getApplication()->getContainer()->get('bundle.toolbar.config')->getBundleConfig();
//        $disableToolbar = (isset($config['disable_toolbar'])) ? $config['disable_toolbar'] : false;
        $disableToolbar = false;
        
        return $this->getRenderer()->partial('partials/bbtoolbar.twig', array('wrapper' => $wrapper, 'disableToolbar' => $disableToolbar));
    }
}
