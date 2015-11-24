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

use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\CoreDomain\Site\Layout;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class LayoutController extends AbstractRestController
{
    /**
     * Returns every workflow states associated to provided layout.
     *
     * @param Layout $layout
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     */
    public function getWorkflowStateAction($uid)
    {
        $layout = $this->getLayoutOr404($uid);

        $layout_states = $this->getDoctrine()->getManager()
            ->getRepository('BackBee\CoreDomain\Workflow\State')
            ->getWorkflowStatesForLayout($layout)
        ;

        $states = array(
            'online'  => array(),
            'offline' => array(),
        );

        foreach ($layout_states as $state) {
            if (0 < $code = $state->getCode()) {
                $states['online'][$code] = array(
                    'label' => $state->getLabel(),
                    'code'  => '1_'.$code,
                );
            } else {
                $states['offline'][$code] = array(
                    'label' => $state->getLabel(),
                    'code'  => '0_'.$code,
                );
            }
        }

        $translator = $this->get('translator');

        $states = array_merge(
            array('0' => array('label' => $translator->trans('offline'), 'code' => '0')),
            $states['offline'],
            array('1' => array('label' => $translator->trans('online'), 'code' => '1')),
            $states['online']
        );

        return new JsonResponse(array_values($states), 200, array(
            'Content-Range' => '0-'.(count($states) - 1).'/'.count($states),
        ));
    }

    public function getCollectionAction(Request $request)
    {
        $qb = $this->getDoctrine()->getManager()
            ->getRepository('BackBee\CoreDomain\Site\Layout')
            ->createQueryBuilder('l')
//            ->select('l, st')
            ->orderBy('l.label', 'ASC')
            ->leftJoin('l.states', 'st')
        ;

        if ($site_uid= ($site = $request->get('site_uid'))) {
            $qb->select('l, st, si')
                ->innerJoin('l.site', 'si', 'WITH', 'si._uid = :site_uid')
                ->setParameter('site_uid', $site_uid)
            ;
        } else {
            $qb->select('l, st')
                ->andWhere('l.site IS NULL')
            ;
        }

        $layouts = $qb->getQuery()->getResult();

        $response = new JsonResponse(null, 200, array(
            'Content-Range' => '0-'.(count($layouts) - 1).'/'.count($layouts),
        ));

        $response->setContent($this->formatCollection($layouts));

        return $response;
    }

    /**
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('VIEW', layout)")
     */
    public function getAction($uid)
    {
        $layout = $this->getLayoutOr404($uid);

        $response = new JsonResponse();
        $response->setContent($this->formatItem($layout));

        return $response;
    }

    protected function getLayoutOr404($uid)
    {
        if (!$layout = $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\Site\Layout')->find($uid)) {
            return new JsonResponse(null, 404);
        }

        return $layout;
    }
}
