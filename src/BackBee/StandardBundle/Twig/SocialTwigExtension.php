<?php
/**
 * @author    Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 * @date      26/11/2015
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\StandardBundle\Twig;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class SocialTwigExtension extends \Twig_Extension
{
    /**
     * @var []
     */
    private $configArray;

    /**
     * @param $configArray
     */
    public function __construct($configArray)
    {
        $this->configArray = $configArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'bbstandard_social' => new \Twig_Function_Method($this, 'renderSocial',[
                'needs_environment' => true
            ]),
        ];
    }

    /**
     * @param \Twig_Environment $twig
     *
     * @return string
     */
    public function renderSocial(\Twig_Environment $twig)
    {
        return $twig->render(
            'BackBeeStandardBundle:partials:socialNetwork.html.twig',
            ['social_networks' => $this->configArray]
        );
    }

    /*
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bbstandard_social_extension';
    }
}