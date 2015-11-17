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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\ApiBundle\Exception\ValidationException;
use BackBee\Security\Group;

/**
 * User Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class GroupController extends AbstractRestController
{
    /**
     * Get all records.
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getCollectionAction(Request $request)
    {
        if ($request->request->has('site_uid')) {
            $site_uid = $request->request->get('site_uid');
            $this->checkSiteUid($site_uid);
        } else {
            $site_uid = $this->getApplication()->getSite()->getUid();
        }

        $groups = $this->getEntityManager()
                ->getRepository('BackBee\Security\Group')
                ->createQueryBuilder('g')
                ->where('g._site = :siteUid')
                ->orWhere('g._site IS NULL')
                ->setParameter(':siteUid', $site_uid)
                ->getQuery()
                ->getResult();

        return new Response($this->formatCollection($groups), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * GET Group.
     *
     * @ParamConverter(name="group", id_name = "id", class="BackBee\Security\Group")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('VIEW', group)")
     */
    public function getAction(Group $group)
    {
        return new Response($this->formatItem($group), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * DELETE.
     *
     * @ParamConverter(name="group", id_name = "id", class="BackBee\Security\Group")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('DELETE', group)")
     */
    public function deleteAction(Group $group)
    {
        $this->getEntityManager()->remove($group);
        $this->getEntityManager()->flush();

        return new Response("", 204);
    }

    /**
     * UPDATE.
     *
     * @Rest\RequestParam(name = "name", requirements = {
     *   @Assert\NotBlank(message="Name is required"),
     *   @Assert\Length(max=50, minMessage="Maximum length of name is 50 characters")
     * })
     *
     * @ParamConverter(name="group", id_name = "id", class="BackBee\Security\Group")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('EDIT', group) & is_granted('VIEW', group)")
     */
    public function putAction(Group $group, Request $request)
    {
        $site = $this->getSite($request);

        if ($this->isDuplicated($request->request->get('name'), $site)) {
            return new JsonResponse([
                'errors' => [
                    'name' => 'Group already exists.',
                ],
            ], 400);
        }

        $this->deserializeEntity($request->request->all(), $group);

        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();

        return new Response("", 204);
    }

    /**
     * Create.
     *
     * @Rest\RequestParam(name = "name", requirements = {
     *  @Assert\NotBlank(message="Name is required"),
     *  @Assert\Length(max=50, minMessage="Maximum length of name is 50 characters")
     * })
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('CREATE', '\BackBee\Security\Group')")
     */
    public function postAction(Request $request)
    {
        $group = new Group();

        $site = $this->getSite($request);

        if ($this->isDuplicated($request->request->get('name'), $site)) {
            return new JsonResponse([
                'errors' => [
                    'name' => 'Group already exists.',
                ],
            ], 400);
        }

        $group->setName($request->request->get('name'));
        $group->setSite($site);

        $group = $this->deserializeEntity($request->request->all(), $group);

        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();

        return new Response($this->formatItem($group), 200, ['Content-Type' => 'application/json']);
    }

    private function getSite(Request $request)
    {
        if ($request->request->has('site_uid')) {
            $this->checkSiteUid($request->request->get('site_uid'));

            $site = $this->getEntityManager()->find('BackBee\CoreDomain\Site\Site', $request->request->get('site_uid'));
        } else {
            $site = $this->getApplication()->getSite();
        }
        return $site;
    }

    private function checkSiteUid($site_uid)
    {
        $site = $this->getEntityManager()->find('BackBee\CoreDomain\Site\Site', $site_uid);
        $validator = Validation::createValidator();
        $constraint = new Assert\NotNull(['message' => 'Invalid site identifier']);
        $violations = $validator->validateValue($site, $constraint);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }

    private function isDuplicated($name, $site) {
        $duplicate = $this->getEntityManager()->getRepository('BackBee\Security\Group')->findOneBy([
            '_name' => $name,
            '_site' => $site,
        ]);

        return $duplicate !== null;
    }
}
