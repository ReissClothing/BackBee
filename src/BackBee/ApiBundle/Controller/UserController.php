<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

use BackBee\Event\Event;
use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\ApiBundle\Exception\ValidationException;
use BackBee\ApiBundle\Patcher\EntityPatcher;
use BackBee\ApiBundle\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\ApiBundle\Patcher\OperationSyntaxValidator;
use BackBee\ApiBundle\Patcher\RightManager;
use BackBee\CoreDomain\Security\User;
use BackBee\Security\Token\BBUserToken;

/**
 * User Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class UserController extends AbstractRestController
{
    /**
     * Get all records.
     *
     *
     * @Rest\QueryParam(name = "limit", default="100", description="Max results", requirements = {
     *  @Assert\Range(max=1000, min=1, minMessage="The value should be between 1 and 1000", maxMessage="The value should be between 1 and 1000"),
     * })
     *
     * @Rest\QueryParam(name = "start", default="0", description="Offset", requirements = {
     *  @Assert\Type(type="digit", message="The value should be a positive number"),
     * })
     */
    public function getCollectionAction(Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to access');
        }

        if (!$this->isGranted('VIEW', new ObjectIdentity('class', get_class($this->getUser())))) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to view users'));
        }

        $group = $request->query->get('groups', null);

        if ($group !== null) {
            $group = $this->getEntityManager()->find('BackBee\Security\Group', $group);
            $users = $group->getUsers();
        } elseif (count($request->query->all()) !== 0) {
            $users = $this->getEntityManager()
                        ->getRepository(get_class($this->getUser()))
                        ->getCollection($request->query->all());
        } else {
            $users = $this->getEntityManager()->getRepository(get_class($this->getUser()))->findAll();
        }

        return new Response($this->formatCollection($users), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * GET current User.
     */
    public function getCurrentAction()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to access');
        }

        $user = $this->getEntityManager()->find(get_class($this->getUser()), $this->getUser()->getId());

        return new Response($this->formatItem($user), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * GET User.
     *
     * @param int $id User ID
     */
    public function getAction($id)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to delete users');
        }

        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        if (!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        if (!$this->isGranted('VIEW', $user)) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to view user with id %s', $id));
        }

        return new Response($this->formatItem($user), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * DELETE User.
     *
     * @param int $id User ID
     */
    public function deleteAction($id)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to delete users');
        }

        if (intval($id) === $this->getUser()->getId()) {
            throw new InsufficientAuthenticationException('You can remove the user of your current session.');
        }

        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        if (!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        if (!$this->isGranted('DELETE', $user)) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to delete user with id %s', $id));
        }

        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();

        return new Response("", 204);
    }

    public function checkIdentity($username, $password)
    {
        $created = date('Y-m-d H:i:s');
        $token = new BBUserToken();
        $token->setUser($request->request->get('username'));
        $token->setCreated($created);
        $token->setNonce(md5(uniqid('', true)));
        $token->setDigest(md5($token->getNonce().$created.md5($password)));

        $tokenAuthenticated = $this->getApplication()->getSecurityContext()->getAuthenticationManager()
            ->authenticate($token)
        ;

        $this->getApplication()->getSecurityContext()->setToken($tokenAuthenticated);
    }

    /**
     * UPDATE User.
     *
     * @Rest\RequestParam(name = "login", requirements = {
     *  @Assert\NotBlank(message="Login is required"),
     *  @Assert\Length(min=6, minMessage="Minimum length of the login is 6 characters")
     * })
     * @Rest\RequestParam(name = "email", requirements = {
     *  @Assert\NotBlank(message="Email not provided"),
     *  @Assert\Email(checkMX=true, message="Email invalid")
     * })
     *
     * @param int $id User ID
     */
    public function putAction($id, Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to view users');
        }

        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        if (!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        if (!$this->isGranted('EDIT', $user)) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to view user with id %s', $id));
        }

        $user = $this->deserializeEntity($request->request->all(), $user);

        if ($request->request->has('password')) {
            $encoderFactory = $this->getContainer()->get('security.context')->getEncoderFactory();
            $password = $request->request->get('password', '');

            if ($encoderFactory && $encoder = $encoderFactory->getEncoder($user)) {
                $password = $encoder->encodePassword($password, '');
            }

            $user->setPassword($password);
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        return new Response("", 204);
    }

    /**
     * Create User.
     *
     * @Rest\RequestParam(name = "login", requirements = {
     *  @Assert\NotBlank(message="Login is required"),
     *  @Assert\Length(min=6, minMessage="Your login must be at least 6 characters")
     * })
     * @Rest\RequestParam(name = "email", requirements = {
     *  @Assert\NotBlank(message="Email not provided"),
     *  @Assert\Email(checkMX=true, message="Email invalid")
     * })
     */
    public function postAction(Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to view users');
        }

        $userExists = $this->getApplication()
                           ->getEntityManager()
                           ->getRepository(get_class($this->getUser()))
                           ->findBy(array('_username' => $request->request->get('username')));

        if ($userExists) {
            throw new ConflictHttpException(sprintf('User with that login already exists: %s', $request->request->get('login')));
        }

        $user = new User();

        if (!$this->isGranted('CREATE', new ObjectIdentity('class', get_class($user)))) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to create users'));
        }

        $user = $this->deserializeEntity($request->request->all(), $user);
        // handle the password
        if ($request->request->has('password')) {
            $password = $request->request->get('password');
        } elseif ($request->request->has('generate_password') && true === $request->request->get('generate_password')) {
            $password = substr(hash('sha512', rand()), 0, 6);
        } else {
            return new JsonResponse([
                'errors' => [
                    'password' => ['Password not provided.'],
                ],
            ], 400);
        }

        $user->setRawPassword($password);
        $encoderFactory = $this->getContainer()->get('security.context')->getEncoderFactory();

        if ($encoderFactory && $encoder = $encoderFactory->getEncoder($user)) {
            $password = $encoder->encodePassword($password, '');
        }

        $user->setPassword($password);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        $event = new Event($user);
        $this->getApplication()->getEventDispatcher()->dispatch('rest.user.creation', $event);

        return new Response($this->formatItem($user), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * PATCH User
     *
     * @param int $id User ID
     */
    public function patchAction($id, Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new InsufficientAuthenticationException('You must be authenticated to view users');
        }
        $actionFound = false;

        $operations = $request->request->all();

        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: '.$e->getMessage());
        }

        $entity_patcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));

        foreach ($operations as $key => $operation) {
            if ('/email' === $operation['path']) {
                $actionFound = $this->patchUserIdentity($id, $operations);
            } elseif ('/groups' === $operation['path']) {
                $actionFound = $this->patchUserGroups($id, $operations);
            } elseif ('/password' === $operation['path']) {
                $actionFound = $this->patchUserPassword($id, $operations);
            } elseif ('/activated' === $operation['path']) {
                $actionFound = $this->patchUserStatus($id, $operations);
            }
        }

        if ($actionFound) {
            return new Response("", 204);
        } else {
            return $this->create404Response('Action not found');
        }
    }

    private function flattenPatchRequest($operations)
    {
        $op = [];
        foreach ($operations as $key => $operation) {
            $op[substr($operation['path'], 1)] = $operation['value'];
        }
        return $op;
    }

    private function patchUserStatus($id, $operations)
    {
        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        if (!$this->isGranted('EDIT', $user)) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to edit user with id %s', $id));
        }

        $operation = reset($operations);

        $user->setActivated((boolean) $operation['value']);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        return true;
    }

    private function patchUserGroups($id, $operations)
    {
        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        if (!$this->isGranted('EDIT', $user)) {
            throw new InsufficientAuthenticationException(sprintf('You are not authorized to edit user with id %s', $id));
        }

        $operations = $this->flattenPatchRequest($operations);

        foreach ($operations['groups'] as $key => $value) {
            if ($value == 'added' || $value == 'removed') {
                $group = $this->getEntityManager()->find('BackBee\Security\Group', $key);

                if ($value == 'added') {
                    $group->addUser($user);
                } else if ($value == 'removed') {
                    $group->removeUser($user);
                }

                $this->getEntityManager()->persist($group);
                $this->getEntityManager()->flush($group);
            }
        }

        return true;
    }


    private function patchUserIdentity($id, $operations)
    {
        if ($this->getUser()->getId() != $id) {
            throw new InsufficientAuthenticationException('Identity can only be changed by its owner.');
        }

        $operations = $this->flattenPatchRequest($operations);

        $validator = Validation::createValidator();
        $constraint = new Assert\Email(['message' => 'Invalid e-mail', 'checkMX' => true]);
        $violations = $validator->validateValue($operations['email'], $constraint);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        $user->setFirstname($operations['firstname']);
        $user->setLastname($operations['lastname']);
        $user->setEmail($operations['email']);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        return true;
    }

    /**
     *
     * @param User    $user    [description]
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    private function patchUserPassword($id, $operations)
    {
        if ($this->getUser()->getId() != $id) {
            throw new InsufficientAuthenticationException('Password can only be changed by its owner.');
        }

        $user = $this->getEntityManager()->find(get_class($this->getUser()), $id);

        $operations = $this->flattenPatchRequest($operations);

        if ($user->getState() !== User::PASSWORD_NOT_PICKED) {
            $this->checkIdentity($user->getLogin(), $operations['old_password']);
        }

        if ($operations['password'] !== $operations['confirm_password']) {
            return new JsonResponse([
                'errors' => [
                    'password' => ['Password and confirm password are differents.'],
                ],
            ], 400);
        }
        $password = trim($operations['password']);
        if (strlen($password) < 5) {
            return new JsonResponse([
                'errors' => [
                    'password' => ['Password to short.'],
                ],
            ], 400);
        }

        $encoderFactory = $this->getContainer()->get('security.context')->getEncoderFactory();

        if ($encoderFactory && $encoder = $encoderFactory->getEncoder($user)) {
            $password = $encoder->encodePassword($password, '');
        }


        $user->setPassword($password);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        return true;
    }
}
