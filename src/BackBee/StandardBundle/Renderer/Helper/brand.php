<?php

namespace BackBee\StandardBundle\Renderer\Helper;

use BackBee\WebBundle\Renderer\Helper\AbstractHelper;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class brand extends AbstractHelper
{
    /**
     * Returns the brand of the current site
     *
     * @param  boolean $alt if true, it will return the alternative brand name
     * @return string
     */
    public function __invoke($alt = false)
    {
        return $this->_renderer->getApplication()->getSite()->getLabel();
    }
}
