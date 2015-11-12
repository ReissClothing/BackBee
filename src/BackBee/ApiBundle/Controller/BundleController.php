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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;

use BackBee\Bundle\AbstractBundleController;
use BackBee\Bundle\BundleControllerResolver;
use BackBee\Bundle\BundleInterface;
use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\ApiBundle\Patcher\EntityPatcher;
use BackBee\ApiBundle\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\ApiBundle\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\ApiBundle\Patcher\OperationSyntaxValidator;
use BackBee\ApiBundle\Patcher\RightManager;

/**
 * REST API for application bundles.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class BundleController extends AbstractRestController
{
    /**
     * Returns a collection of declared bundles.
     */
    public function getCollectionAction()
    {
        $bundles = array();
        foreach ($this->getApplication()->getBundles() as $bundle) {
            if ($this->isGranted('EDIT', $bundle) || ($bundle->isEnabled() && $this->isGranted('VIEW', $bundle))) {
                $bundles[] = $bundle;
            }
        }

        return $this->createJsonResponse($bundles, 200, array(
            'Content-Range' => '0-'.(count($bundles) - 1).'/'.count($bundles),
        ));
    }

    /**
     * Returns the bundle with id $id if it exists, else a 404 response will be generated.
     *
     * @param string $id the id of the bundle we are looking for
     */
    public function getAction($id)
    {
        $bundle = $this->getBundleById($id);

        try {
            $this->granted('EDIT', $bundle);
        } catch (\Exception $e) {
            if ($bundle->isEnabled()) {
                $this->granted('VIEW', $bundle);
            } else {
                throw $e;
            }
        }

        return $this->createJsonResponse($bundle);
    }

    /**
     * Patch the bundle.
     *
     * @Rest\RequestParam(name="0", description="Patch operations", requirements={
     *   @Assert\NotBlank(message="Request must contain at least one operation")
     * })
     *
     * @param string $id the id of the bundle we are looking for
     */
    public function patchAction($id, Request $request)
    {
        $bundle = $this->getBundleById($id);

        $this->granted('EDIT', $bundle);
        $operations = $request->request->all();

        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: '.$e->getMessage());
        }

        $entity_patcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));
        $entity_patcher->getRightManager()->addAuthorizationMapping($bundle, array(
            'category'        => array('replace'),
            'config_per_site' => array('replace'),
            'enable'          => array('replace'),
        ));

        try {
            $entity_patcher->patch($bundle, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new BadRequestHttpException('Invalid patch operation: '.$e->getMessage());
        }

        $this->getApplication()->getContainer()->get('config.persistor')->persist(
            $bundle->getConfig(),
            null !== $bundle->getConfig()->getProperty('config_per_site')
                ? $bundle->getConfig()->getProperty('config_per_site')
                : false
        );

        return $this->createJsonResponse(null, 204);
    }

    /**
     * This method is the front controller of every bundles exposed actions.
     *
     * @param string $bundleName     name of bundle we want to reach its exposed actions
     * @param string $controllerName controller name
     * @param string $actionName     name of exposed action we want to reach
     * @param string $parameters     optionnal, action's parameters
     *
     * @return Response              Bundle Controller Response
     */
    public function accessBundleExposedRoutesAction($bundleName, $controllerName, $actionName, $parameters)
    {
        $bundle = $this->getBundleById($bundleName);

        $controller = (new BundleControllerResolver($this->getApplication()))->resolve($bundleName, $controllerName);

        if ($controller instanceof AbstractBundleController) {
            $controller->setBundle($bundle);
        }

        if (false === empty($parameters)) {
            $parameters = array_filter(explode('/', $parameters));
        }

        $response = call_user_func_array([$controller, $actionName], (array)$parameters);

        return is_object($response) && $response instanceof Response
            ? $response
            : $this->createJsonResponse($response)
        ;
    }

    /**
     * @see BackBee\ApiBundle\Controller\ARestController::granted
     */
    protected function granted($attributes, $object = null, $message = 'Access denied')
    {
        try {
            parent::granted($attributes, $object);
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException(
                'Acces denied: no "'
                .(is_array($attributes) ? implode(', ', $attributes) : $attributes)
                .'" rights for bundle '.get_class($object).'.'
            );
        }

        return true;
    }

    /**
     * Returns a bundle by id.
     *
     * @param string $id
     *
     * @throws NotFoundHttpException is raise if no bundle was found with provided id
     *
     * @return BundleInterface
     */
    private function getBundleById($id)
    {
        if (null === $bundle = $this->getApplication()->getBundle($id)) {
            throw new NotFoundHttpException("No bundle exists with id `$id`");
        }

        return $bundle;
    }
}
