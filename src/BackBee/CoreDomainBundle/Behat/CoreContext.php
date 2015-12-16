<?php
/**
 * @author    Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 * @date      14/12/2015
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\CoreDomainBundle\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class CoreContext extends DefaultContext
{
    use KernelDictionary;

    /**
     * @var
     */
    private $filesPath;

    public function __construct($filesPath)
    {

        $this->filesPath = $filesPath;
    }
    /**
     * @BeforeScenario
     */
    function initializeStorage(BeforeScenarioScope $scope)
    {
//        Leave this commented out for now !!
//        $this->getContainer()->get('reiss.zone.cache')->deleteAll();
//        $this->getContainer()->get('reiss.taxon.prefix_provider.storage')->deleteAll();

        $entityManager = $this->getService('doctrine.orm.entity_manager');
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $con = $this->getService('database_connection');

        $con->executeUpdate("SET foreign_key_checks = 0;");

        $con->exec(file_get_contents($this->filesPath));

        $con->executeUpdate("SET foreign_key_checks = 1;");
    }
}