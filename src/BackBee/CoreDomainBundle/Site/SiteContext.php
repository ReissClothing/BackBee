<?php
/**
 * @author    Gonzalo Vilaseca <gvf.vilaseca@reiss.com>
 * @date      05/11/15
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\CoreDomainBundle\Site;

/**
 * @author Gonzalo Vilaseca <gvf.vilaseca@reiss.com>
 */
class SiteContext
{
    private $site;

    private $siteResolver;

    public function __construct(SiteResolver $siteResolver)
    {
        $this->siteResolver = $siteResolver;
    }

    public function getSite()
    {
        if (!$this->site) {
            $this->site = $this->siteResolver->resolve();
        }

        return $this->site;
    }
}