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

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Exception\InvalidContentTypeException;
use BackBee\ApiBundle\Controller\Annotations as Rest;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * ClassContent API Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ClassContentController extends AbstractRestController
{
    /**
     * @var BackBee\ClassContent\ClassContentManager
     */
    private $manager;

    /**
     * Returns category's datas if $id is valid.
     *
     * @param string $id category's id
     *
     * @return Response
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getCategoryAction($id)
    {
        $category = $this->getCategoryManager()->getCategory($id);
        if (null === $category) {
            throw new NotFoundHttpException("Classcontent's category `$id` not found.");
        }

        return $this->createJsonResponse($category);
    }

    /**
     * Returns every availables categories datas.
     *
     * @return Response
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getCategoryCollectionAction()
    {
        $categories = [];
        foreach ($this->getCategoryManager()->getCategories() as $id => $category) {
            $categories[] = array_merge(['id' => $id], $category->jsonSerialize());
        }

        return $this->addContentRangeHeadersToResponse(new JsonResponse($categories), $categories, 0);
    }

    /**
     * Returns collection of classcontent associated to category and according to provided criterias.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
//    public function getCollectionAction($start, $count, Request $request)
    public function getCollectionAction($start = 25, $count = 100, Request $request)
    {
        $contents = [];
        $format = $this->getFormatParam();
        $response = new JsonResponse();
        $categoryName = $request->query->get('category', null);

        if (AbstractClassContent::JSON_DEFINITION_FORMAT === $format) {
            $response->setData($contents = $this->getClassContentDefinitionsByCategory($categoryName));
            $start = 0;
        } else {
            if (null !== $categoryName) {
                $contents = $this->getClassContentByCategory($categoryName, $start, $count);
            } else {
                $classnames = $this->getClassContentManager()->getAllClassContentClassnames();
                $contents = $this->findContentsByCriteria($classnames, $start, $count);
            }

            $data = $this->getClassContentManager()->jsonEncodeCollection($contents, $this->getFormatParam());
            $response->setData($data);
        }

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Returns collection of classcontent associated to $type and according to provided criterias.
     *
     * @param string $type
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getCollectionByTypeAction($type, $start, $count)
    {
        $classname = AbstractClassContent::getClassnameByContentType($type);
        $contents = $this->findContentsByCriteria((array) $classname, $start, $count);
        $response = new JsonResponse($this->getClassContentManager()->jsonEncodeCollection(
            $contents,
            $this->getFormatParam()
        ));

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Get classcontent.
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\QueryParam(name="mode", description="The render mode to use")
     * @Rest\QueryParam(name="page_uid", description="The page to set to application's renderer before rendering")
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getAction($type, $uid, Request $request)
    {
        $this->granted('VIEW', $content = $this->getClassContentByTypeAndUid($type, $uid, true));

        $response = null;
        if (in_array('text/html', $request->getAcceptableContentTypes())) {
            if (false != $pageUid = $request->query->get('page_uid')) {
                if (null !== $page = $this->getEntityManager()->find('BackBee\CoreDomain\NestedNode\Page', $pageUid)) {
                    $this->getApplication()->getRenderer()->setCurrentPage($page);
                }
            }

            $mode = $request->query->get('mode', null);
            $response = $this->createResponse(
                $this->getApplication()->getRenderer()->render($content, $mode), 200, 'text/html'
            );
        } else {
            $response = $this->createJsonResponse();
            $response->setData($this->getClassContentManager()->jsonEncode($content, $this->getFormatParam()));
        }

        return $response;
    }

    /**
     * Creates classcontent according to provided type.
     *
     * @param string  $type
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function postAction($type, Request $request)
    {
        $classname = AbstractClassContent::getClassnameByContentType($type);
        $content = new $classname();
        $this->granted('CREATE', $content);

        $this->getEntityManager()->persist($content);
        $content->setDraft($this->getClassContentManager()->getDraft($content, true));

        $this->getEntityManager()->flush();

        $data = $request->request->all();
        if (0 < count($data)) {
            $data = array_merge($data, [
                'type' => $type,
                'uid'  => $content->getUid(),
            ]);

            $this->updateClassContent($type, $data['uid'], $data);
            $this->getEntityManager()->flush();
        }

        return $this->createJsonResponse(null, 201, [
            'BB-RESOURCE-UID' => $content->getUid(),
            'Location'        => $this->getApplication()->getRouting()->getUrlByRouteName(
                'bb.rest.classcontent.get',
                [
                    'version' => $request->attributes->get('version'),
                    'type'    => $type,
                    'uid'     => $content->getUid(),
                ],
                '',
                false
            ),
        ]);
    }

    /**
     * Updates classcontent's elements and parameters.
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function putAction($type, $uid, Request $request)
    {
        $this->updateClassContent($type, $uid, $request->request->all());
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Updates collection of classcontent elements and parameters.
     *
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function putCollectionAction(Request $request)
    {
        $result = [];
        foreach ($request->request->all() as $data) {
            if (!isset($data['type']) || !isset($data['uid'])) {
                throw new BadRequestHttpException("type and/or uid is missing.");
            }

            try {
                $content = $this->updateClassContent($data['type'], $data['uid'], $data);
                $this->granted('VIEW', $content);
                $this->granted('EDIT', $content);

                $result[] = [
                    'uid'        => $content->getUid(),
                    'type'       => $content->getContentType(),
                    'statusCode' => 200,
                    'message'    => 'OK',
                ];
            } catch (AccessDeniedException $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 401,
                    'message'    => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 500,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse($result);
    }

    /**
     * delete a classcontent.
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function deleteAction($type, $uid)
    {
        $this->granted('DELETE', $content = $this->getClassContentByTypeAndUid($type, $uid));

        try {
            $this->getEntityManager()->getRepository('BackBee\CoreDomain\ClassContent\AbstractClassContent')->deleteContent($content);
            $this->getEntityManager()->flush();
        } catch (\Exception $e) {
            throw new BadRequestHttpException("Unable to delete content with type: `$type` and uid: `$uid`");
        }

        return $this->createJsonResponse(null, 204);
    }

    /**
     * ClassContent's draft getter.
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getDraftAction($type, $uid)
    {
        $this->granted('VIEW', $content = $this->getClassContentByTypeAndUid($type, $uid));

        return $this->createJsonResponse($this->getClassContentManager()->getDraft($content));
    }

    /**
     * Returns all drafts of current user.
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function getDraftCollectionAction()
    {
        $contents = $this->getEntityManager()
            ->getRepository('BackBee\CoreDomain\ClassContent\Revision')
            ->getAllDrafts($this->getApplication()->getBBUserToken())
        ;

        $contents = $this->sortDraftCollection($contents);

        return $this->addContentRangeHeadersToResponse($this->createJsonResponse($contents), $contents, 0);
    }

    /**
     * Updates a classcontent's draft.
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function putDraftAction($type, $uid, Request $request)
    {
        $this->updateClassContentDraft($type, $uid, $request->request->all());
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Updates collection of classcontents' drafts.
     *
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function putDraftCollectionAction(Request $request)
    {
        $result = [];
        foreach ($request->request->all() as $data) {
            if (!isset($data['type']) || !isset($data['uid'])) {
                throw new BadRequestHttpException("type and/or uid is missing.");
            }

            try {
                $content = $this->updateClassContentDraft($data['type'], $data['uid'], $data);
                $result[] = [
                    'uid'        => $content->getUid(),
                    'type'       => $content->getContentType(),
                    'statusCode' => 200,
                    'message'    => 'OK',
                ];
            } catch (AccessDeniedException $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 401,
                    'message'    => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 500,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse($result);
    }

    /**
     * Getter of classcontent category manager.
     *
     * @return BackBee\ClassContent\CategoryManager
     */
    private function getCategoryManager()
    {
        return $this->get('classcontent.category_manager');
    }

    /**
     * Returns ClassContentManager.
     *
     * @return BackBee\ClassContent\ClassContentManager
     */
    private function getClassContentManager()
    {
        return $this->get('classcontent.manager');
    }

    /**
     * Sorts the provided array that contains current logged user's drafts.
     *
     * @param array $drafts
     *
     * @return array
     */
    private function sortDraftCollection(array $drafts)
    {
        $sortedDrafts = [];
        $filteredDrafts = [];

        foreach ($drafts as $key => $draft) {
            if (null === $draft->getContent()) {
                continue;
            }

            $sortedDrafts[$draft->getContent()->getUid()] = [$draft->getContent()->getUid() => $draft];
            $filteredDrafts[$key] = $draft;
        }

        foreach ($filteredDrafts as $draft) {
            foreach ($draft->getContent()->getData() as $key => $element) {
                if (
                    is_object($element)
                    && $element instanceof AbstractClassContent
                    && in_array($element->getUid(), array_keys($sortedDrafts))
                ) {
                    $elementUid = $element->getUid();
                    $sortedDrafts[$draft->getContent()->getUid()][$key] = $sortedDrafts[$elementUid][$elementUid];
                }
            }
        }

        $drafts = [];
        foreach ($sortedDrafts as $key => $data) {
            if (!array_key_exists($key, $drafts)) {
                $drafts[$key] = $data;
            }

            foreach ($data as $elementName => $draft) {
                if ($key === $elementName) {
                    continue;
                }

                if (false === $drafts[$key]) {
                    $drafts[$draft->getContent()->getUid()] = false;
                } elseif (isset($sortedDrafts[$draft->getContent()->getUid()])) {
                    $drafts[$key][$elementName] = $sortedDrafts[$draft->getContent()->getUid()];
                    $drafts[$draft->getContent()->getUid()] = false;
                }
            }
        }

        return array_filter($drafts);
    }

    /**
     * Updates and returns content and its draft according to provided data.
     *
     * @param string $type
     * @param string $uid
     * @param array  $data
     *
     * @return AbstractClassContent
     */
    private function updateClassContent($type, $uid, $data)
    {
        $this->granted('EDIT', $content = $this->getClassContentByTypeAndUid($type, $uid, true, true));
        $this->getClassContentManager()->update($content, $data);

        return $content;
    }

    /**
     * Commits or reverts content's draft according to provided data.
     *
     * @param string $type
     * @param string $uid
     * @param array  $data
     *
     * @return AbstractClassContent
     */
    private function updateClassContentDraft($type, $uid, $data)
    {
        $this->granted('VIEW', $content = $this->getClassContentByTypeAndUid($type, $uid));
        $this->granted('EDIT', $content);

        $operation = $data['operation'];
        if (!in_array($operation, ['commit', 'revert'])) {
            throw new BadRequestHttpException(sprintf('%s is not a valid operation for update draft.', $operation));
        }

        $this->getClassContentManager()->$operation($content, $data);

        return $content;
    }

    /**
     * Returns classcontent datas if couple (type;uid) is valid.
     *
     * @param string $type
     * @param string $uid
     *
     * @return AbstractClassContent
     */
    private function getClassContentByTypeAndUid($type, $uid, $hydrateDraft = false, $checkoutOnMissing = false)
    {
        $content = null;

        try {
            $content = $this->getClassContentManager()->findOneByTypeAndUid(
                $type,
                $uid,
                $hydrateDraft,
                $checkoutOnMissing
            );
        } catch (InvalidContentTypeException $e) {
            throw new NotFoundHttpException(sprintf('Provided content type (:%s) is invalid.', $type));
        }

        if (null === $content) {
            throw new NotFoundHttpException(sprintf('Cannot find `%s` with uid `%s`.', $type, $uid));
        }

        return $content;
    }

    /**
     * Returns classcontent by category.
     *
     * @param string  $name  category's name
     * @param integer $start
     * @param integer $count
     *
     * @return null|Paginator
     */
    private function getClassContentByCategory($name, $start, $count)
    {
        return $this->findContentsByCriteria($this->getClassContentClassnamesByCategory($name), $start, $count);
    }

    /**
     * Returns all classcontents classnames that belong to provided category.
     *
     * @param string $name The category name
     *
     * @return array
     */
    private function getClassContentClassnamesByCategory($name)
    {
        try {
            return $this->getCategoryManager()->getClassContentClassnamesByCategory($name);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }

    /**
     * Returns classcontent data with definition format (AbstractContent::JSON_DEFAULT_FORMAT). If category name
     * is provided it will returns every classcontent definition that belongs to this category, else it
     * will returns all classcontents definitions.
     *
     * @param string|null $name the category's name or null
     *
     * @return array
     */
    private function getClassContentDefinitionsByCategory($name = null)
    {
        $classnames = [];
        if (null === $name) {
            $classnames = $this->getClassContentManager()->getAllClassContentClassnames();
        } else {
            $classnames = $this->getClassContentClassnamesByCategory($name);
        }

        $definitions = [];
        foreach ($classnames as $classname) {
            $definitions[] = $this->getClassContentManager()->jsonEncode(
                (new $classname()),
                AbstractClassContent::JSON_DEFINITION_FORMAT
            );
        }

        return $definitions;
    }

    /**
     * Find classcontents by provided classnames, criterias from request, provided start and count.
     *
     * @param array   $classnames
     * @param integer $start
     * @param integer $count
     *
     * @return null|Paginator
     */
    private function findContentsByCriteria(array $classnames, $start, $count)
    {
        $criterias = array_merge([
            'only_online' => false,
            'site_uid'    => $this->getApplication()->getSite()->getUid(),
        ], $this->getRequest()->query->all());

        $criterias['only_online'] = (boolean) $criterias['only_online'];

        $order_infos = [
            'column'    => isset($criterias['order_by']) ? $criterias['order_by'] : '_modified',
            'direction' => isset($criterias['order_direction']) ? $criterias['order_direction'] : 'desc',
        ];

        $pagination = ['start' => $start, 'limit' => $count];

        unset($criterias['order_by']);
        unset($criterias['order_direction']);

        $criterias['contentIds'] = array_filter(explode(',', $this->getRequest()->query->get('uids', '')));

        unset($criterias['uids']);

        $contents = $this->getEntityManager()
            ->getRepository('BackBee\CoreDomain\ClassContent\AbstractClassContent')
            ->findContentsBySearch($classnames, $order_infos, $pagination, $criterias)
        ;

        foreach ($contents as $content) {
            $content->setDraft($this->getClassContentManager()->getDraft($content));
        }

        return $contents;
    }

    /**
     * Returns AbstractContent valid json format by looking at request query parameter and if no format found,
     * it fallback to AbstractContent::JSON_DEFAULT_FORMAT.
     *
     * @return integer One of AbstractContent::$jsonFormats:
     *                 JSON_DEFAULT_FORMAT | JSON_DEFINITION_FORMAT | JSON_CONCISE_FORMAT | JSON_INFO_FORMAT
     */
    private function getFormatParam()
    {
        $validFormats = array_keys(AbstractClassContent::$jsonFormats);
        $queryParamsKey = array_keys($this->getRequest()->query->all());
        $format = ($collection = array_intersect($validFormats, $queryParamsKey))
            ? array_shift($collection)
            : $validFormats[AbstractClassContent::JSON_DEFAULT_FORMAT]
        ;

        return AbstractClassContent::$jsonFormats[$format];
    }

    /**
     * Add 'Content-Range' parameters to $response headers.
     *
     * @param Response $response   the response object
     * @param mixed    $collection collection from where we extract Content-Range data
     * @param integer  $start      the start value
     */
    private function addContentRangeHeadersToResponse(Response $response, $collection, $start)
    {
        $count = count($collection);
        if ($collection instanceof Paginator) {
            $count = count($collection->getIterator());
        }

        $lastResult = $start + $count - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$start-$lastResult/".count($collection));

        return $response;
    }
}
