<?php
/**
 * @author    Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 * @date      14/12/2015
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\ApiBundle\Behat;

use BackBee\CoreDomain\Security\User;
use BackBee\CoreDomainBundle\Behat\DefaultContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit_Framework_Assert as Assertions;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class WebApiContext extends DefaultContext implements SnippetAcceptingContext
{
    /**
     * @Given I am logged in with role :role
     */
    public function iAmLoggedInWithRole($role)
    {
        $this->iAmLoggedInAsRole($role, 'backbee@example.com', array($role));
    }

    /**
     * Create user and login with given role.
     *
     * @param string $role
     * @param string $email
     * @param array  $authorizationRoles
     */
    private function iAmLoggedInAsRole($role, $email = 'backbee@example.com', array $authorizationRoles = array())
    {
        $user = $this->thereIsUser($email, 'backbee', $role, 'yes', null, array(), true, $authorizationRoles);

        $token = new UsernamePasswordToken($user, $user->getPassword(), 'administration', $user->getRoles());

        $session = $this->getService('session');
        $session->set('_security_main', serialize($token));
        $session->save();

        $this->prepareSessionIfNeeded();

        $this->getSession()->setCookie($session->getName(), $session->getId());
        $this->getService('security.token_storage')->setToken($token);
    }

    /**
     * @param        $email
     * @param        $password
     * @param null   $role
     * @param string $enabled
     * @param null   $address
     * @param array  $groups
     * @param bool   $flush
     * @param array  $authorizationRoles
     * @param null   $createdAt
     *
     * @return UserInterface
     */
    public function thereIsUser($email, $password, $role = null, $enabled = 'yes', $address = null, $groups = array(), $flush = true, array $authorizationRoles = array(), $createdAt = null)
    {
        if (null !== $user = $this->getRepository('user')->findOneByEmail($email)) {
            return $user;
        }

        /* @var $user UserInterface */
        $user = $this->createUser($email, $password, $role, $enabled, $address, $groups, $authorizationRoles, $createdAt);

        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $user;
    }

    /**
     * @Then the response should contain json:
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $etalon = json_decode($jsonString->getRaw(), true);

        $a = $this->getSession()->getPage();
//        $b = $this->getSession()->getPage()->getHtml();
        $c = $this->getSession()->getPage()->getContent();

        $actual = json_decode($this->getMink()->getSession()->getPage()->getContent(), true);

        if (null === $etalon) {
            throw new \RuntimeException("Can not convert etalon to json:\n");
        }
        Assertions::assertGreaterThanOrEqual(count($etalon), count($actual));

        foreach ($etalon as $key => $needle) {
            Assertions::assertArrayHasKey($key, $actual);
            $e = $etalon[$key];
            $g = $actual[$key];
            Assertions::assertEquals($etalon[$key], $actual[$key]);
        }
    }

    /**
     * @param $email
     * @param $password
     * @param $role
     * @param $enabled
     * @param $address
     * @param $groups
     * @param array $authorizationRoles
     * @param $createdAt
     *
     * @return UserInterface
     */
    protected function createUser($email, $password, $role = null, $enabled = 'yes', $address = null, array $groups = array(), array $authorizationRoles = array(), $createdAt = null)
    {
        // @todo move to factory! and refactor user controller
        $user = new User();
        $user->setUsername($email);
        $user->setEmail($email);
        $user->setActivated('yes' === $enabled);
        $user->setRawPassword($password);
        $encoderFactory = $this->getService('security.encoder_factory');

        if ($encoderFactory && $encoder = $encoderFactory->getEncoder($user)) {
            $password = $encoder->encodePassword($password, '');
        }

        $user->setPassword($password);

        $user->setFirstname('BB');
        $user->setLastname('Testing');

        // @todo gvf roles are hardcoded into user in this alpha, shoul dbe added properly here once refactored
        return $user;
    }


    private function prepareSessionIfNeeded()
    {
//        if (!$this->getSession()->getDriver() instanceof Selenium2Driver) {
            return;
//        }

        if (false !== strpos($this->getSession()->getCurrentUrl(), $this->getMinkParameter('base_url'))) {
            return;
        }

        $this->visitPath('/');
    }
}