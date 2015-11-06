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

use Doctrine\ORM\EntityRepository;

/**
 * @author Gonzalo Vilaseca <gvf.vilaseca@reiss.com>
 */
class SiteResolver
{
//    @GVF TODO put site repo interface?
    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @param EntityRepository $entityRepository
     */
    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    public function resolve()
    {
//        @TODO gvf retrieve the proper site
        return $this->entityRepository->findOneBy(array());
    }
}