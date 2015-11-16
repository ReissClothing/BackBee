<?php

namespace BackBee\ToolbarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConfigController extends Controller
{
    public function getAction()
    {
        return new JsonResponse(
            $settings = array(
                'core'                 =>
                    array(
                        'ApplicationManager' =>
                            array(
                                'appPath'      => '/bundles/backbeeweb/js/bb-core-js/src/tb/apps',
                                'active'       => 'main',
                                'route'        => '',
                                'applications' =>
                                    array(
                                        'main'         =>
                                            array(
                                                'label'  => 'Main',
                                                'config' =>
                                                    array(
                                                        'mainRoute' => 'appMain/index',
                                                    ),
                                            ),
                                        'content'      =>
                                            array(
                                                'label'  => 'Edition du contenu',
                                                'config' =>
                                                    array(),
                                            ),
                                        'bundle'       =>
                                            array(
                                                'label'  => 'Bundle',
                                                'config' =>
                                                    array(
                                                        'mainRoute' => 'bundle/index',
                                                    ),
                                            ),
                                        'page'         =>
                                            array(
                                                'label'  => 'Page',
                                                'config' =>
                                                    array(
                                                        'mainRoute' => 'page/index',
                                                    ),
                                            ),
                                        'contribution' =>
                                            array(
                                                'label'  => 'Contribution',
                                                'config' =>
                                                    array(
                                                        'mainRoute' => 'contribution/index',
                                                    ),
                                            ),
                                        'user'         =>
                                            array(
                                                'label'  => 'User',
                                                'config' =>
                                                    array(
                                                        'mainRoute' => 'user/index',
                                                    ),
                                                'scope'  =>
                                                    array(
                                                        'global' =>
                                                            array(
                                                                'open' => 'user.showCurrent',
                                                            ),
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
                'wrapper_toolbar_id'   => 'bb5-ui',
                'default_url'          => 'content/contribution/edit',
                'component'            =>
                    array(
                        'logger'            =>
                            array(
                                'level' => 8,
                                'mode'  => 'devel',
                            ),
                        'exceptions-viewer' =>
                            array(
                                'show'          => true,
                                'showInConsole' => true,
                            ),
                        'medialibrary'      =>
                            array(
                                'available_media' =>
                                    array(
                                        0 =>
                                            array(
                                                'title' => 'Image',
                                                'type'  => 'Media/Image',
                                                'ico'   => 'fa fa-picture',
                                            ),
                                        1 =>
                                            array(
                                                'title' => 'Pdf',
                                                'type'  => 'Media/Pdf',
                                                'ico'   => 'fa fa-file-pdf-o',
                                            ),
                                    ),
                            ),
                        'translator'        =>
                            array(
                                'base'           => '/bundles/backbeeweb/js/bb-core-js/src/tb/i18n',
                                'default_locale' => 'en_US',
                                'locales'        =>
                                    array(
                                        'en_US' => 'EN',
                                        'fr_FR' => 'FR',
                                        'ru_RU' => 'RU',
                                    ),
                            ),
                    ),
                'unclickable_contents' =>
                    array(
                        'contents' =>
                            array(
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
            )
        );
    }
}
