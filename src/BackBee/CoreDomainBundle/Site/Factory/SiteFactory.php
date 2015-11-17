<?php
/**
 * @author    Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 * @date      17/11/2015
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\CoreDomainBundle\Site\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class SiteFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityRepository $entityRepository
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getSite($uid)
    {
        return $this->entityManager->getRepository('BackBee\CoreDomain\Site\Site')->findOneBy(array("_uid" => $uid));
    }
}