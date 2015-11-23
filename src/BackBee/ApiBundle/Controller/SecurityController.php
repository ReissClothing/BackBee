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

use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\Security\Token\BBUserToken;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Exception\DisabledException;

/**
 * Auth Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SecurityController extends AbstractRestController
{
    /**
     * @Rest\RequestParam(name="username", requirements={@Assert\NotBlank})
     * @Rest\RequestParam(name="password", requirements={@Assert\NotBlank})
     */
//not used anymore
    public function authenticateAction(Request $request)
    {
//        $created = date('Y-m-d H:i:s');
//        $token = new BBUserToken();
//        $token->setUser($request->request->get('username'));
//        $token->setCreated($created);
//        $token->setNonce(md5(uniqid('', true)));
//        $token->setDigest(md5($token->getNonce().$created.md5($request->request->get('password'))));
//
//        $tokenAuthenticated = $this->getApplication()->getSecurityContext()->getAuthenticationManager()
//            ->authenticate($token)
//        ;
//
//        if (!$tokenAuthenticated->getUser()->getApiKeyEnabled()) {
//            throw new DisabledException('API access forbidden');
//        }
//
//        $this->getApplication()->getSecurityContext()->setToken($tokenAuthenticated);
//
//        return $this->createJsonResponse(null, 201, array(
//            'X-API-KEY'       => $tokenAuthenticated->getUser()->getApiKeyPublic(),
//            'X-API-SIGNATURE' => $tokenAuthenticated->getNonce(),
//        ));
//    }
}
