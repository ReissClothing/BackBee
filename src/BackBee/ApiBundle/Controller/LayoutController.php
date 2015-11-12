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
     * @Rest\ParamConverter(name="layout", class="BackBee\CoreDomain\Site\Layout")
     */
    public function getWorkflowStateAction(Layout $layout)
    {
        $layout_states = $this->getApplication()->getEntityManager()
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

        $translator = $this->getApplication()->getContainer()->get('translator');

        $states = array_merge(
            array('0' => array('label' => $translator->trans('offline'), 'code' => '0')),
            $states['offline'],
            array('1' => array('label' => $translator->trans('online'), 'code' => '1')),
            $states['online']
        );

        return $this->createJsonResponse(array_values($states), 200, array(
            'Content-Range' => '0-'.(count($states) - 1).'/'.count($states),
        ));
    }

    /**
     * @Rest\ParamConverter(
     *   name="site", id_name="site_uid", id_source="query", class="BackBee\CoreDomain\Site\Site", required=false
     * )
     */
    public function getCollectionAction(Request $request)
    {
        $qb = $this->getEntityManager()
            ->getRepository('BackBee\CoreDomain\Site\Layout')
            ->createQueryBuilder('l')
            ->select('l, st')
            ->orderBy('l._label', 'ASC')
            ->leftJoin('l._states', 'st')
        ;

        if (null !== ($site = $request->attributes->get('site'))) {
            $qb->select('l, st, si')
                ->innerJoin('l._site', 'si', 'WITH', 'si._uid = :site_uid')
                ->setParameter('site_uid', $site->getUid())
            ;
        } else {
            $qb->select('l, st')
                ->andWhere('l._site IS NULL')
            ;
        }

        $layouts = $qb->getQuery()->getResult();

        $response = $this->createJsonResponse(null, 200, array(
            'Content-Range' => '0-'.(count($layouts) - 1).'/'.count($layouts),
        ));

        $response->setContent($this->formatCollection($layouts));

        return $response;
    }

    /**
     * @Rest\ParamConverter(name="layout", class="BackBee\CoreDomain\Site\Layout")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('VIEW', layout)")
     */
    public function getAction(Layout $layout)
    {
        $response = $this->createJsonResponse();
        $response->setContent($this->formatItem($layout));

        return $response;
    }
}
