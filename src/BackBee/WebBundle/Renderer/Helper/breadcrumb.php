<?php

namespace BackBee\WebBundle\Renderer\Helper;

class breadcrumb extends AbstractHelper
{

    public function __invoke($showCurrentItem = true)
    {
        $application = $this->_renderer->getApplication();
        $repository = $application->getEntityManager()->getRepository('BackBee\CoreDomain\NestedNode\Page');

        $currentPage = $this->_renderer->getCurrentPage();
        $ancestors = array();

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
