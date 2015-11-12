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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\ApiBundle\Exception\ValidationException;

/**
 * User Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class AclController extends AbstractRestController
{
    /**
     * Get all records.
     *
     * @Rest\QueryParam(name = "group_id", description="Security Group ID", requirements = {
     *  @Assert\NotBlank(message="Group ID cannot be empty")
     * })
     * @Rest\QueryParam(name = "object_id", description="Object ID", requirements = {
     *  @Assert\NotBlank(message="Object ID cannot be empty")
     * })
     * @Rest\QueryParam(name = "object_class", description="Object Class name", requirements = {
     *  @Assert\NotBlank(message="Object Class cannot be empty")
     * })
     * @Rest\QueryParam(name = "mask", description="Permission Mask", requirements = {
     *  @Assert\NotBlank(message="Mask must be provided"),
     *  @Assert\Type(type="integer", message="Mask must be an integer"),
     * })
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getEntryCollectionAction(Request $request)
    {
        $aclProvider = $this->getApplication()->getSecurityContext()->getACLProvider();

        /* @var $aclProvider \Symfony\Component\Security\Acl\Dbal\AclProvider */
        $aclProvider->findAcls();

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from('BackBee\Security\Group', 'g')
        ;

        if ($request->request->get('site_uid')) {
            $site = $this->getEntityManager()->getRepository('BackBee\CoreDomain\Site\Site')
                ->find($request->request->get('site_uid'))
            ;

            if (!$site) {
                throw $this->createValidationException(
                    'site_uid',
                    $request->request->get('site_uid'),
                    'Site is not valid: '.$request->request->get('site_uid')
                );
            }

            $qb->leftJoin('g._site', 's')
                ->andWhere('s._uid = :site_uid')
                ->setParameter('site_uid', $site->getUid())
            ;
        }

        $groups = $qb->getQuery()->getResult();

        return new Response($this->formatCollection($groups));
    }

    /**
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getClassCollectionAction(Request $request)
    {
        $sql = 'SELECT * FROM acl_classes';

        $results = $this->getEntityManager()->getConnection()->fetchAll($sql);

        return new Response(json_encode($results));
    }

    /**
     * @Rest\RequestParam(name = "group_id", description="Security Group ID", requirements = {
     *  @Assert\NotBlank(message="Group ID cannot be empty")
     * })
     *
     * @Rest\RequestParam(name = "object_class", description="Object Class name", requirements = {
     *  @Assert\NotBlank(message="Object Class cannot be empty")
     * })
     *
     * @Rest\RequestParam(name = "mask", description="Permission Mask", requirements = {
     *  @Assert\NotBlank(message="Mask must be provided"),
     *  @Assert\Type(type="integer", message="Mask must be an integer"),
     * })
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function postClassAceAction(Request $request)
    {
        $objectIdentity = new ObjectIdentity('class', $request->request->get('object_class'));

        $aclProvider = $this->getApplication()->getSecurityContext()->getACLProvider();

        $aclManager = $this->getContainer()->get("security.acl_manager");
        $acl = $aclManager->getAcl($objectIdentity);

        $securityIdentity = new UserSecurityIdentity($request->request->get('group_id'), 'BackBee\Security\Group');

        // grant owner access
        $acl->insertClassAce($securityIdentity, $request->request->get('mask'));

        $aclProvider->updateAcl($acl);

        $aces = $acl->getClassAces();

        $ace = $aces[0];
        /* @var $ace \Symfony\Component\Security\Acl\Domain\Entry */

        $data = [
            'id' => $ace->getId(),
            'mask' => $ace->getMask(),
            'group_id' => $ace->getSecurityIdentity()->getUsername(),
            'object_class' => $ace->getAcl()->getObjectIdentity()->getType(),
        ];

        return new Response(json_encode($data), 201);
    }

    /**
     * @Rest\RequestParam(name = "group_id", description="Security Group ID", requirements = {
     *  @Assert\NotBlank(message="Group ID cannot be empty")
     * })
     *
     * @Rest\RequestParam(name = "object_class", description="Object Class name", requirements = {
     *  @Assert\NotBlank(message="Object Class cannot be empty")
     * })
     *
     * @Rest\RequestParam(name = "object_id", description="Object ID", requirements = {
     *  @Assert\NotBlank(message="Object ID cannot be empty")
     * })
     *
     * @Rest\RequestParam(name = "mask", description="Permission Mask", requirements = {
     *  @Assert\NotBlank(message="Mask must be provided"),
     *  @Assert\Type(type="integer", message="Mask must be an integer"),
     * })
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function postObjectAceAction(Request $request)
    {
        $objectIdentity = new ObjectIdentity($request->request->get('object_id'), $request->request->get('object_class'));
        $aclProvider = $this->getApplication()->getSecurityContext()->getACLProvider();

        $aclManager = $this->getContainer()->get("security.acl_manager");

        $acl = $aclManager->getAcl($objectIdentity);

        $securityIdentity = new UserSecurityIdentity($request->request->get('group_id'), 'BackBee\Security\Group');

        // grant owner access
        $acl->insertObjectAce($securityIdentity, $request->request->get('mask'));

        $aclProvider->updateAcl($acl);

        $aces = $acl->getObjectAces();

        $ace = $aces[0];
        /* @var $ace \Symfony\Component\Security\Acl\Domain\Entry */

        $data = [
            'id' => $ace->getId(),
            'mask' => $ace->getMask(),
            'group_id' => $ace->getSecurityIdentity()->getUsername(),
            'object_class' => $ace->getAcl()->getObjectIdentity()->getType(),
            'object_id' => $ace->getAcl()->getObjectIdentity()->getIdentifier(),
        ];

        return new Response(json_encode($data), 201);
    }

    /**
     * Bulk permissions create/update.
     *
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function postPermissionMapAction(Request $request)
    {
        $permissionMap = $request->request->all();
        $aclManager = $this->getContainer()->get("security.acl_manager");

        $violations = new ConstraintViolationList();

        foreach ($permissionMap as $i => $objectMap) {
            $permissions = $objectMap['permissions'];

            if (!isset($objectMap['object_class'])) {
                $violations->add(
                    new ConstraintViolation(
                        "Object class not supllied",
                        "Object class not supllied",
                        [],
                        sprintf('%s[object_class]', $i),
                        sprintf('%s[object_class]', $i),
                        null
                    )
                );
                continue;
            }

            $objectClass = $objectMap['object_class'];
            $objectId = null;

            if (!class_exists($objectClass)) {
                $violations->add(
                    new ConstraintViolation(
                        "Class $objectClass doesn't exist",
                        "Class $objectClass doesn't exist",
                        [],
                        sprintf('%s[object_class]', $i),
                        sprintf('%s[object_class]', $i),
                        $objectClass
                    )
                );
                continue;
            }

            $objectIdentity = null;

            if (isset($objectMap['object_id'])) {
                $objectId = $objectMap['object_id'];
                // object scope
                $objectIdentity = new ObjectIdentity($objectId, $objectClass);
            } else {
                // class scope
                $objectIdentity = new ObjectIdentity('class', $objectClass);
            }

            if (!isset($objectMap['sid'])) {
                $violations->add(
                    new ConstraintViolation(
                        "Security ID not supllied",
                        "Security ID not supllied",
                        [],
                        sprintf('%s[sid]', $i),
                        sprintf('%s[sid]', $i),
                        null
                    )
                );
                continue;
            }

            $sid = $objectMap['sid'];
            $securityIdentity = new UserSecurityIdentity($sid, 'BackBee\Security\Group');

            // convert values to booleans
            $permissions = array_map(function ($val) {
                return \BackBee\Utils\StringUtils::toBoolean((string) $val);
            }, $permissions);

            // remove false values
            $permissions = array_filter($permissions);
            $permissions = array_keys($permissions);
            $permissions = array_unique($permissions);

            try {
                $mask = $aclManager->getMask($permissions);
            } catch (\BackBee\Security\Acl\Permission\InvalidPermissionException $e) {
                $violations->add(
                    new ConstraintViolation(
                        $e->getMessage(),
                        $e->getMessage(),
                        [],
                        sprintf('%s[permissions]', $i),
                        sprintf('%s[permissions]', $i),
                        $e->getPermission()
                    )
                );
                continue;
            }

            if ($objectId) {
                $aclManager->insertOrUpdateObjectAce($objectIdentity, $securityIdentity, $mask);
            } else {
                $aclManager->insertOrUpdateClassAce($objectIdentity, $securityIdentity, $mask);
            }
        }

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return new Response('', 204);
    }

    /**
     * @Rest\RequestParam(name = "object_class", description="Object Class name", requirements = {
     *  @Assert\NotBlank(message="Object Class cannot be empty")
     * })
     *
     * @param string|int $sid
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function deleteClassAceAction($sid, Request $request)
    {
        $aclManager = $this->getContainer()->get("security.acl_manager");
        $securityIdentity = new UserSecurityIdentity($sid, 'BackBee\Security\Group');
        $objectIdentity = new ObjectIdentity('class', $request->request->get('object_class'));

        try {
            $aclManager->deleteClassAce($objectIdentity, $securityIdentity);
        } catch (\InvalidArgumentException $ex) {
            throw $this->createValidationException(
                'object_class',
                $request->request->get('object_class'),
                sprintf("Class ace doesn't exist for class %s", $request->request->get('object_class'))
            );
        }

        return new Response('', 204);
    }

    /**
     * @Rest\RequestParam(name = "object_class", description="Object Class name", requirements = {
     *  @Assert\NotBlank(message="Object Class cannot be empty")
     * })
     *
     * @Rest\RequestParam(name = "object_id", description="Object Identifier", requirements = {
     *  @Assert\NotBlank(message="Object Identifier cannot be empty")
     * })
     *
     * @param string|int $sid
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function deleteObjectAceAction($sid, Request $request)
    {
        $aclManager = $this->getContainer()->get("security.acl_manager");
        $securityIdentity = new UserSecurityIdentity($sid, 'BackBee\Security\Group');
        $objectClass = $request->request->get('object_class');

        $objectIdentity = new ObjectIdentity($request->request->get('object_id'), $objectClass);

        try {
            $aclManager->deleteClassAce($objectIdentity, $securityIdentity);
        } catch (\InvalidArgumentException $ex) {
            throw $this->createValidationException(
                'object',
                $request->request->get('object_class'),
                sprintf("Object ace doesn't exist for %s::%s", $objectClass, $request->request->get('object_id'))
            );
        }

        return new Response('', 204);
    }

    /**
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getMaskCollectionAction()
    {
        $aclManager = $this->getContainer()->get("security.acl_manager");

        $data = $aclManager->getPermissionCodes();

        return new Response(json_encode($data), 200);
    }
}
