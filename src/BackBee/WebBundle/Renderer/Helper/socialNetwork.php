<?php

namespace BackBee\WebBundle\Renderer\Helper;

class socialNetwork extends AbstractHelper
{

    public function __invoke($showCurrentItem = true)
    {
        $application = $this->_renderer->getApplication();
// @todo gvf
//        $socialsNetworks = $application->getConfig()->getSection('social_network');
        $socialsNetworks =  array (
            'facebook' =>
                array (
                    'link' => 'https://www.facebook.com/backbeeCMS',
                    'fa_icon' => 'facebook',
                    'title' => 'Facebook',
                ),
            'twitter' =>
                array (
                    'link' => 'https://twitter.com/lpdigitalsystem',
                    'widget_id' => '606481211662475264',
                    'fa_icon' => 'twitter',
                    'title' => 'Twitter',
                ),
            'google' =>
                array (
                    'link' => 'https://plus.google.com/101416676508957143369',
                    'fa_icon' => 'google-plus',
                    'title' => 'Google +',
                ),
        );

        $render = $this->_renderer->partial('partials/socialNetwork.twig', [
            'social_networks' => $socialsNetworks
        ]);

        return $render;
    }
}
