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
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Tools\Pagination\Paginator;
use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\CoreDomain\NestedNode\KeyWord;
use BackBee\ApiBundle\Patcher\EntityPatcher;
use BackBee\ApiBundle\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\ApiBundle\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\ApiBundle\Patcher\RightManager;
use BackBee\ApiBundle\Patcher\OperationSyntaxValidator;

/**
 * ClassContent API Controller.
 *
 * @author h.baptiste
 */
class KeywordController extends AbstractRestController
{
    /**
     * @param Request $request
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     *
     * @ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBee\CoreDomain\NestedNode\KeyWord", required=false
     * )
     */
    public function getCollectionAction(Request $request, $start, $count, KeyWord $parent = null)
    {
        $results = [];
        $term = $request->query->get('term', null);
        if (null !== $term) {
            $results = $this->getKeywordRepository()->getLikeKeyWords($term);
        } else {
            $orderInfos = [
                'field' => '_leftnode',
                'dir' => 'asc',
            ];
            $results = $this->getKeywordRepository()->getKeyWords($parent, $orderInfos, array('start' => $start, 'limit' => $count));
        }
        $total = count($results);
        if ($results instanceof Paginator) {
            $results = iterator_to_array($results->getIterator());
        }

        return $this->addRangeToContent($this->createJsonResponse($results), $total, $start, count($results));
    }

    /**
     * Get Keyword by uid.
     *
     * @param string $uid the unique identifier of the page we want to retrieve
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter(name="keyword", class="BackBee\CoreDomain\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getAction(KeyWord $keyword)
    {
        return $this->createJsonResponse($keyword);
    }

    /**
     * @return Response
     *
     * @ParamConverter(name="keyword", class="BackBee\CoreDomain\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function deleteAction(KeyWord $keyword = null)
    {
        try {
            if (!$keyword) {
                throw new BadRequestHttpException('A keyword should be provided.');
            }

            /* delete only if this keyword is not linked to a content */
            if (!$keyword->getContent()->isEmpty()) {
                throw new BadRequestHttpException('KEYWORD_IS_LINKED');
            }
            $this->getKeywordRepository()->delete($keyword);
            $response = $this->createJsonResponse(null, 204);
        } catch (\Exception $e) {
            $response = $this->createErrorResponse($e);
        }

        return $response;
    }

    /**
     * @param KeyWord $keyword
     * @param Request $request
     *
     * @return Response
     *
     * @ParamConverter(name="keyword", class="BackBee\CoreDomain\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function patchAction(KeyWord $keyword, Request $request)
    {
        $operations = $request->request->all();
        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: '.$e->getMessage());
        }

        $this->patchSiblingAndParentOperation($keyword, $operations);
        $entityPatcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));
        try {
            $entityPatcher->patch($keyword, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new BadRequestHttpException('Invalid patch operation: '.$e->getMessage());
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    private function addRangeToContent(Response $response, $total, $start, $nbItems)
    {
        $lastResult = $start + $nbItems - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$start-$lastResult/".$total);

        return $response;
    }

    private function patchSiblingAndParentOperation(KeyWord $keyword, &$operations)
    {
        $siblingOperation = null;
        $parentOperation = null;

        foreach ($operations as $key => $operation) {
            $op = array('key' => $key, 'op' => $operation);
            if ('/sibling_uid' === $operation['path']) {
                $siblingOperation = $op;
            } elseif ('/parent_uid' === $operation['path']) {
                $parentOperation = $op;
            }
        }

        if (null !== $siblingOperation || null !== $parentOperation) {
            if ($keyword->isRoot()) {
                throw new BadRequestHttpException('Cannot move root node of a site.');
            }
            try {
                if (null !== $siblingOperation) {
                    unset($operations[$siblingOperation['key']]);

                    $sibling = $this->getKeywordByUid($siblingOperation['op']['value']);
                    $this->getKeywordRepository()->moveAsPrevSiblingOf($keyword, $sibling);
                } elseif (null !== $parentOperation) {
                    unset($operations[$parentOperation['key']]);

                    $parent = $this->getKeywordByUid($parentOperation['op']['value']);
                    $this->getKeywordRepository()->moveAsLastChildOf($keyword, $parent);
                }
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException(sprintf('Invalid node move action: %s', $e->getMessage()));
            }
        }
    }

    /**
     * Create a keyword object
     * and if a parent is provided add the keyword as its last child.
     *
     * @param KeyWord $keyword
     *
     * @Rest\RequestParam(name="keyword", description="Keyword value", requirements={
     *   @Assert\NotBlank()
     * })
     * @ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\CoreDomain\NestedNode\KeyWord", required=false
     * )
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function postAction(Request $request, $parent = null)
    {
        try {
            $keyWordLabel = trim($request->request->get('keyword'));
            $uid = $request->request->get('uid', null);
            if (null !== $uid) {
                $keywordItem = $this->getKeywordRepository()->find($uid);
                $keywordItem->setKeyWord($keyWordLabel);
            } else {
                $keywordItem = new KeyWord();
                $keywordItem->setKeyWord($keyWordLabel);
                if (null === $parent) {
                    $parent = $this->getKeywordRepository()->getRoot();
                }

                if ($this->keywordAlreadyExists($keyWordLabel)) {
                    throw new BadRequestHttpException('KEYWORD_ALREADY_EXISTS');
                }
                $keywordItem->setParent($parent);
                $this->getKeywordRepository()->insertNodeAsLastChildOf($keywordItem, $parent);
            }

            $this->getDoctrine()->getManager()->persist($keywordItem);
            $this->getDoctrine()->getManager()->flush();

            $response = $this->createJsonResponse(null, 201, [
                'BB-RESOURCE-UID' => $keywordItem->getUid(),
                'Location' => $this->get('router')->generate(
                    'bb.rest.keyword.get',
                    [
                        'version' => $request->attributes->get('version'),
                        'uid' => $keywordItem->getUid(),
                    ],
                )
            ]);
        } catch (\Exception $e) {
            $response = $this->createErrorResponse($e);
        }

        return $response;
    }

    private function createErrorResponse(\Exception $e)
    {
        return $this->createJsonResponse(array('statusCode' => 500, 'message' => $e->getMessage()), 500);
    }

    /**
     * @return Response
     *
     * @Rest\RequestParam(name="keyword", description="Keyword value", requirements={
     *      @Assert\NotBlank()
     * })
     * @ParamConverter(name="keyword", class="BackBee\CoreDomain\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function putAction(KeyWord $keyword, Request $request)
    {
        try {
            $keywordLabel = trim($request->request->get('keyword'));

            if ($this->keywordAlreadyExists($keywordLabel, $keyword->getUid())) {
                throw new BadRequestHttpException('KEYWORD_ALREADY_EXISTS');
            }

            $keyword->setKeyWord($keywordLabel);

            $this->getDoctrine()->getManager()->persist($keyword);
            $this->getDoctrine()->getManager()->flush();

            $response = $this->createJsonResponse(null, 204);
        } catch (\Exception $e) {
            $response = $this->createErrorResponse($e);
        }

        return $response;
    }

    private function keywordAlreadyExists($keywordLabel, $kwUid = null)
    {
        $kwExists = false;
        $keywordItem = $this->getKeywordRepository()->findOneBy([
            '_keyWord' => strtolower(trim($keywordLabel)),
        ]);

        if (null !== $keywordItem && $keywordItem->getUid() !== $kwUid) {
            $kwExists = true;
        }

        return $kwExists;
    }

    private function getKeywordByUid($uid)
    {
        if (null === $keyword = $this->getKeywordRepository()->find($uid)) {
            throw new NotFoundHttpException("Unable to find keyword with uid `$uid`");
        }

        return $keyword;
    }

    private function getKeywordRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\NestedNode\KeyWord');
    }
}
