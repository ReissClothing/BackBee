<?php

namespace BackBee\StandardBundle\Renderer\Helper;

use BackBee\WebBundle\Renderer\Helper\AbstractHelper;

class breadcrumb extends AbstractHelper
{
    public function __invoke($showCurrentItem = true)
    {
        $repository = $this->_renderer->getEntityManager()->getRepository('BackBee\CoreDomain\NestedNode\Page');

        $currentPage = $this->_renderer->getCurrentPage();

        if (null !== $currentPage) {
            $ancestors = $repository->getAncestors($currentPage);
        } else {
            $ancestors = array($this->_renderer->getCurrentRoot());
        }

        $render = $this->_renderer->partial('partials/breadcrumb.twig', [
            'ancestors' => $ancestors,
            'current' => $showCurrentItem ? $currentPage : null
        ]);

        return $render;
    }
}
