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

use Doctrine\ORM\Tools\Pagination\Paginator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Validator\Constraints as Assert;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\Exception\InvalidArgumentException;
use BackBee\MetaData\MetaDataBag;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\ApiBundle\Controller\Annotations as Rest;
use BackBee\ApiBundle\Exception\NotModifiedException;
use BackBee\ApiBundle\Patcher\EntityPatcher;
use BackBee\ApiBundle\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\ApiBundle\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\ApiBundle\Patcher\OperationSyntaxValidator;
use BackBee\ApiBundle\Patcher\RightManager;
use BackBee\CoreDomain\Site\Layout;
use BackBee\CoreDomain\Workflow\State;

/**
 * Page Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PageController extends AbstractRestController
{
    /**
     * Returns page entity available status.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAvailableStatusAction()
    {
        return $this->createJsonResponse(Page::$STATES);
    }

    /**
     * Get page's metadatas.
     *
     * @param Page $page the page we want to get its metadatas
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter(name="page", class="BackBee\CoreDomain\NestedNode\Page")
     */
    public function getMetadataAction(Page $page)
    {
        $metadata = null !== $page->getMetaData() ? $page->getMetaData()->jsonSerialize() : array();
        $default_metadata = new MetaDataBag($this->getApplication()->getConfig()->getSection('metadata'));
        $metadata = array_merge($default_metadata->jsonSerialize(), $metadata);

        return $this->createJsonResponse($metadata);
    }


    /**
     * Get page ancestors
     * @param Page $page the page we want to get its ancestors
     * @return \Symfony\Component\HttpFoundation\Response
     * @ParamConverter(name="page", class="BackBee\CoreDomain\NestedNode\Page")
     */
    public function getAncestorsAction(Page $page)
    {
        $ancestors = $this->getPageRepository()->getAncestors($page);

        return $this->createResponse($this->formatCollection($ancestors));
    }

    /**
     * Update page's metadatas.
     *
     * @param Page    $page    the page we want to update its metadatas
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter(name="page", class="BackBee\CoreDomain\NestedNode\Page")
     */
    public function putMetadataAction(Page $page, Request $request)
    {
        $metadatas = $page->getMetaData();

        foreach ($request->request->all() as $name => $attributes) {
            if ($metadatas->has($name)) {
                foreach ($attributes as $attr_name => $attr_value) {
                    if ($attr_value !== $metadatas->get($name)->getAttribute($attr_name)) {
                        $metadatas->get($name)->setAttribute($attr_name, $attr_value);
                    }
                }
            }
        }

        $page->setMetaData($metadatas->compute($page));
        $this->getDoctrine()->getEntityManager()->flush($page);

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Get collection of page entity.
     *
     * Version 1
     *  - without params return current root
     *  - parent_uid return first level before the parent page
     *
     * Version 2
     *  - without params return all pages
     *  - `parent_uid` return all pages available before the nested level
     *  - `root` return current root
     *  - `level_offset` permit to choose the depth ex: `parent_uid=oneuid&level_offset=1` equals version 1 parent_uid parameter
     *  - `has_children` return only pages they have children
     *  - new available filter params:
     *    - `title` (is a like method)
     *    - `layout_uid`
     *    - `site_uid`
     *    - `created_before`
     *    - `created_after`
     *    - `modified_before`
     *    - `modified_after`
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     *
     * @Rest\QueryParam(name="parent_uid", description="Parent Page UID")
     *
     * @Rest\QueryParam(name="order_by", description="Page order by", requirements={
     *   @Assert\Type(type="array", message="An array containing at least 1 column name to order by must be provided"),
     *   @Assert\All({
     *     @Assert\Choice(choices = {"asc", "desc"}, message="order direction is not valid")
     *   })
     * })
     *
     * @Rest\QueryParam(name="state", description="Page State", requirements={
     *   @Assert\Type(type="array", message="An array containing at least 1 state must be provided"),
     *   @Assert\All({
     *     @Assert\Choice(choices = {"0", "1", "2", "3", "4"}, message="State is not valid")
     *   })
     * })
     *
     * @ParamConverter(
     *   name="parent", options={"id"="parent_uid", "required"=false}, class="BackBee\CoreDomain\NestedNode\Page"
     * )
     */
//    @todo Controller "BackBee\ApiBundle\Controller\PageController::getCollectionAction()" requires that you provide a value for the "$start" argument (because there is no default value or because there is a non optional argument after this one).
//    public function getCollectionAction(Request $request, $start, $count, Page $parent = null)
    public function getCollectionAction(Request $request, $start = 0, $count = 2000, Page $parent = null)
    {
        $response = null;
        $contentUid = $request->query->get('content_uid', null);
        $contentType = $request->query->get('content_type', null);

        if (null !== $contentUid && null !== $contentType) {
            $response = $this->doGetCollectionByContent($contentType, $contentUid);
        } elseif ((null === $contentUid && null !== $contentType) || (null !== $contentUid && null === $contentType)) {
            throw new BadRequestHttpException(
                'To get page collection by content, you must provide `content_uid` and `content_type` as query parameters.'
            );
        } elseif ($request->attributes->get('version') == 1) {
            $response = $this->doClassicGetCollectionVersion1($request, $start, $count, $parent);
        } else {
            $response = $this->doClassicGetCollection($request, $start, $count, $parent);
        }

        return $response;
    }

    /**
     * Get page by uid.
     *
     * @param string $uid the unique identifier of the page we want to retrieve
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("page", class="BackBee\CoreDomain\NestedNode\Page", options={"id" = "uid"})
     * @Rest\Security(expression="is_granted('VIEW', page)")
     */
    public function getAction(Page $page)
    {
        return $this->createResponse($this->formatItem($page));
    }

    /**
     * Create a page.
     *
     * @Rest\RequestParam(name="title", description="Page title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contain at least 3 characters"),
     *   @Assert\NotBlank()
     * })
     *
     * @ParamConverter(
     *   name="layout", id_name="layout_uid", id_source="request", class="BackBee\CoreDomain\Site\Layout", required=true
     * )
     * @ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\CoreDomain\NestedNode\Page", required=false
     * )
     * @ParamConverter(
     *   name="source", id_name="source_uid", id_source="query", class="BackBee\CoreDomain\NestedNode\Page", required=false
     * )
     * @ParamConverter(
     *   name="workflow", id_name="workflow_uid", id_source="request", class="BackBee\CoreDomain\Workflow\State", required=false
     * )
     *
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function postAction(Layout $layout, Request $request, Page $parent = null)
    {
        if (null !== $parent) {
            $this->granted('EDIT', $parent);
        }

        $builder = $this->get('pagebuilder');
        $builder->setLayout($layout);

        if (null !== $parent) {
            $builder->setParent($parent);
            $builder->setRoot($parent->getRoot());
            $builder->setSite($parent->getSite());

            if ($this->isFinal($parent)) {
                return $this->createFinalResponse($parent->getLayout());
            }
        } else {
            $builder->setSite($this->get('bbapp.site_context')->getSite());
        }

        $requestRedirect = $request->request->get('redirect');
        $redirect = ($requestRedirect === '' || $requestRedirect === null) ? null : $requestRedirect;

        $builder->setTitle($request->request->get('title'));
        $builder->setUrl($request->request->get('url', null));
        $builder->setState($request->request->get('state'));
        $builder->setTarget($request->request->get('target'));
        $builder->setRedirect($redirect);
        $builder->setAltTitle($request->request->get('alttitle'));
        $builder->setPublishing(
            null !== $request->request->get('publishing')
                ? new \DateTime(date('c', $request->request->get('publishing')))
                : null
        );

        $builder->setArchiving(
            null !== $request->request->get('archiving')
                ? new \DateTime(date('c', $request->request->get('archiving')))
                : null
        );

        try {
            $page = $builder->getPage();

            $this->trySetPageWorkflowState($page, $this->getEntityFromAttributes('workflow'));
            $this->granted('CREATE', $page);
            $em = $this->getDoctrine()->getManager();
            if (null !== $page->getParent()) {
               $em
                        ->getRepository('BackBee\CoreDomain\NestedNode\Page')
                        ->insertNodeAsFirstChildOf($page, $page->getParent());
            }

            $em->persist($page);
            $em->flush();
        } catch (\Exception $e) {
            return $this->createResponse('Internal server error: '.$e->getMessage(), 500);
        }

        return $this->createJsonResponse('', 201, array(
            'Location' => $this->get('router')->generate(
                'bb.rest.page.get',
                array(
                    'version' => $request->attributes->get('version'),
                    'uid'     => $page->getUid(),
                ),
                '',
                false
            ),
        ));
    }

    private function createFinalResponse(Layout $layout)
    {
        return $this->createResponse('Can\'t create children of ' . $layout->getLabel() . ' layout', 403);
    }

    /**
     * Check if the page is final
     *
     * @param  Page|null $page [description]
     * @return boolean         [description]
     */
    private function isFinal(Page $page = null)
    {
        $result = false;
        if (null !== $page) {
            $layout = $page->getLayout();
            if (null !== $layout && $layout->isFinal()) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Update page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Rest\RequestParam(name="title", description="Page title", requirements={
     *   @Assert\NotBlank(message="title is required")
     * })
     * @Rest\RequestParam(name="url", description="page url", requirements={
     *   @Assert\NotBlank(message="url is required")
     * })
     * @Rest\RequestParam(name="target", description="page target", requirements={
     *   @Assert\NotBlank(message="target is required")
     * })
     * @Rest\RequestParam(name="state", description="page state", requirements={
     *   @Assert\NotBlank(message="state is required")
     * })
     * @Rest\RequestParam(name="publishing", description="Publishing flag", requirements={
     *   @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     * @Rest\RequestParam(name="archiving", description="Archiving flag", requirements={
     *   @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     *
     * @ParamConverter(name="page", class="BackBee\CoreDomain\NestedNode\Page")
     * @ParamConverter(name="layout", id_name="layout_uid", class="BackBee\CoreDomain\Site\Layout", id_source="request")
     * @ParamConverter(
     *   name="parent", id_name="parent_uid", class="BackBee\CoreDomain\NestedNode\Page", id_source="request", required=false
     * )
     * @ParamConverter(
     *   name="workflow", id_name="workflow_uid", id_source="request", class="BackBee\CoreDomain\Workflow\State", required=false
     * )
     * @Rest\Security(expression="is_granted('EDIT', page)")
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function putAction(Page $page, Layout $layout, Request $request, Page $parent = null)
    {

        $page->setLayout($layout);
        $this->trySetPageWorkflowState($page, $this->getEntityFromAttributes('workflow'));

        $requestRedirect = $request->request->get('redirect');
        $redirect = ($requestRedirect === '' || $requestRedirect === null) ? null : $requestRedirect;

        $page->setTitle($request->request->get('title'))
            ->setUrl($request->request->get('url'))
            ->setTarget($request->request->get('target'))
            ->setState($request->request->get('state'))
            ->setRedirect($redirect)
            ->setAltTitle($request->request->get('alttitle', null))
        ;

        if ($parent !== null) {

            $page->setParent($parent);
            if ($this->isFinal($parent)) {
                return $this->createFinalResponse($parent->getLayout());
            }
        }

        if ($request->request->has('publishing')) {
            $publishing = $request->request->get('publishing');
            $page->setPublishing(null !== $publishing ? new \DateTime(date('c', $publishing)) : null);
        }

        if ($request->request->has('archiving')) {
            $archiving = $request->request->get('archiving');
            $page->setArchiving(null !== $archiving ? new \DateTime(date('c', $archiving)) : null);
        }

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Update page collecton.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER')")
     */
    public function putCollectionAction(Request $request)
    {
        $result = [];
        foreach ($request->request->all() as $data) {
            if (!isset($data['uid'])) {
                throw new BadRequestHttpException('uid is missing.');
            }

            try {
                $page = $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\NestedNode\Page')->find($data['uid']);

                $this->granted('EDIT', $page);
                if (isset($data['state'])) {
                    $this->granted('PUBLISH', $page);
                }
                $this->updatePage($page, $data);

                $result[] = [
                    'uid'        => $page->getUid(),
                    'statusCode' => 200,
                    'message'    => 'OK',
                ];
            } catch (NotModifiedException $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'statusCode' => 304,
                    'message'    => $e->getMessage(),
                ];
            } catch (AccessDeniedException $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'statusCode' => 401,
                    'message'    => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                if ($e instanceof BadRequestHttpException || $e instanceof InsufficientAuthenticationException) {
                    $result[] = [
                        'uid'        => $data['uid'],
                        'statusCode' => 403,
                        'message'    => $e->getMessage(),
                    ];
                } else {
                    $result[] = [
                        'uid'        => $data['uid'],
                        'statusCode' => 500,
                        'message'    => $e->getMessage(),
                    ];
                }
            }
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse($result);
    }

    private function updatePage(Page $page, $data)
    {
        if (isset($data['state'])) {
            $this->updatePageState($page, $data['state']);
        }
        if (isset($data['parent_uid'])) {
            $repo = $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\NestedNode\Page');
            $parent = $repo->find($data['parent_uid']);

            if (null !== $parent) {
                $layout = $parent->getLayout();
                if ($layout !== null && $layout->isFinal()) {
                    throw new BadRequestHttpException('Can\'t create children of ' . $layout->getLabel() . ' layout');
                }
            }

            $this->moveAsFirstChildOf($page, $parent);
        }
    }

    private function updatePageState(Page $page, $state)
    {
        if ($state === 'online') {
            if (!$page->isOnline(true)) {
                $page->setState($page->getState() + 1);
            } else {
                throw new NotModifiedException();
            }
        } elseif ($state === 'offline') {
            if ($page->isOnline(true)) {
                $page->setState($page->getState() - 1);
            } else {
                throw new NotModifiedException();
            }
        } elseif ($state === 'delete') {
            if ($page->getState() >= 4) {
                $this->hardDelete($page);
            } else {
                $page->setState(4);
            }
        }
    }

    private function hardDelete(Page $page)
    {
        $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\NestedNode\Page')->deletePage($page);
        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * Patch page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Rest\RequestParam(name="0", description="Patch operations", requirements={
     *   @Assert\NotBlank(message="Request must contain at least one operation")
     * })
     *
     * @ParamConverter(name="page", class="BackBee\CoreDomain\NestedNode\Page")
     * @Rest\Security(expression="is_granted('EDIT', page)")
     */
    public function patchAction(Page $page, Request $request)
    {
        $operations = $request->request->all();

        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: '.$e->getMessage());
        }

        $entity_patcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));
        $entity_patcher->getRightManager()->addAuthorizationMapping($page, array(
            'publishing' => array('replace'),
            'archiving' => array('replace')
        ));

        $this->patchStateOperation($page, $operations);
        $this->patchSiblingAndParentOperation($page, $operations);

        try {
            $entity_patcher->patch($page, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new BadRequestHttpException('Invalid patch operation: '.$e->getMessage());
        }

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Delete page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter(name="page", class="BackBee\CoreDomain\NestedNode\Page")
     */
    public function deleteAction(Page $page)
    {
        if (true === $page->isRoot()) {
            throw new BadRequestHttpException('Cannot remove root page of a site.');
        }

        $this->granted('DELETE', $page);

        $this->granted('EDIT', $page->getParent()); // user must have edit permission on parent

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page); // user must have publish permission on the page
        }

        $this->getPageRepository()->toTrash($page);

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Clone a page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Rest\RequestParam(name="title", description="Cloning page new title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contains atleast 3 characters"),
     *   @Assert\NotBlank
     * })
     *
     * @ParamConverter(name="source", class="BackBee\CoreDomain\NestedNode\Page")
     * @ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\CoreDomain\NestedNode\Page", required=false
     * )
     * @ParamConverter(
     *   name="sibling", id_name="sibling_uid", id_source="request", class="BackBee\CoreDomain\NestedNode\Page", required=false
     * )
     *
     * @Rest\Security(expression="is_granted('CREATE', source)")
     */
    public function cloneAction(Page $source, Page $parent = null, $sibling = null, Request $request)
    {
        // user must have view permission on chosen layout
        $this->granted('VIEW', $source->getLayout());

        if (null !== $sibling) {
            $parent = $sibling->getParent();
        } elseif (null === $parent) {
            $parent = $source->getParent();
        }

        if (null !== $parent) {
            $this->granted('EDIT', $parent);
        } else {
            $this->granted('EDIT', $this->get('bbapp.site_context')->getSite());
        }

        $page = $this->getPageRepository()->duplicate(
            $source,
            $request->request->get('title'),
            $parent,
            true,
            $this->get('security.token_storage')->getToken()
        );

        $this->getDoctrine()->getManager()->persist($page);
        $this->getDoctrine()->getManager()->flush();

        if (null !== $sibling) {
            $this->getPageRepository()->moveAsPrevSiblingOf($page, $sibling);
        }

        return $this->createJsonResponse(null, 201, [
            'Location' => $this->get('router')->generate(
                'bb.rest.page.get',
                [
                    'version' => $request->attributes->get('version'),
                    'uid'     => $page->getUid(),
                ]
                )
            ]
        );
    }

    /**
     * Getter for page entity repository.
     *
     * @return \BackBee\NestedNode\Repository\PageRepository
     */
    private function getPageRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\NestedNode\Page');
    }

    /**
     * Returns every pages that contains provided classcontent.
     *
     * @param string $contentType
     * @param string $contentUid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function doGetCollectionByContent($contentType, $contentUid)
    {
        $content = null;
        $classname = AbstractClassContent::getClassnameByContentType($contentType);
        $em = $this->getDoctrine()->getManager();

        try {
            $content = $em->find($classname, $contentUid);
        } catch (ClassNotFoundException $e) {
            throw new NotFoundHttpException("No classcontent found with provided type (:$contentType)");
        }

        if (null === $content) {
            throw new NotFoundHttpException("No `$classname` exists with uid `$contentUid`");
        }

        $pages = $em->getRepository("BackBee\CoreDomain\ClassContent\AbstractClassContent")->findPagesByContent($content);

        $response = $this->createResponse($this->formatCollection($pages));
        if (0 < count($pages)) {
            $response->headers->set('Content-Range', '0-'.(count($pages) - 1).'/'.count($pages));
        }

        return $response;
    }

    /**
     * Returns pages collection by doing classic selection and by applying filters provided in request
     * query parameters.
     *
     * @param Request   $request
     * @param integer   $start
     * @param integer   $count
     * @param Page|null $parent
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function doClassicGetCollectionVersion1(Request $request, $start, $count, Page $parent = null)
    {
        $qb = $this->getPageRepository()
                    ->createQueryBuilder('p');
        $orderBy = [
            '_position' => 'ASC',
            '_leftnode' => 'ASC',
        ];
        if (null !== $request->query->get('order_by', null)) {
            foreach ($request->query->get('order_by') as $key => $value) {
                if ('_' !== $key[0]) {
                    $key = '_' . $key;
                }
                $orderBy[$key] = $value;
            }
        }
        if (null === $parent) {
            $qb->andSiteIs($this->getApplication()->getSite())
                    ->andParentIs(null);
        } else {
            $this->granted('VIEW', $parent);
            $qb->andIsDescendantOf($parent, true, 1, $orderBy, $count, $start);
        }
        if (null !== $state = $request->query->get('state', null)) {
            $qb->andStateIsIn((array) $state);
        }

        return $this->paginateClassicCollectionAction($qb, $start, $count);
    }

    /**
     * Returns pages collection by doing classic selection and by applying filters provided in request
     * query parameters.
     *
     * @param Request   $request
     * @param integer   $start
     * @param integer   $count
     * @param Page|null $parent
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function doClassicGetCollection(Request $request, $start, $count, Page $parent = null)
    {
        $qb = $this->getPageRepository()
                    ->createQueryBuilder('p');

        if (null !== $parent) {
            $this->granted('VIEW', $parent);

            // Ordering is disabled for descendants, BackBee takes care of that
            $qb->andIsDescendantOf($parent, true, $request->query->get('level_offset', 1), $this->getOrderCriteria(), $count, $start);
        } else {
            if ($request->query->has('site_uid')) {
                $site = $this->getSiteRepository()->find($request->query->get('site_uid'));
                if (!$site) {
                    throw new BadRequestHttpException(sprintf("There is no site with uid: %s", $request->query->get('site_uid')));
                }
                $qb->andSiteIs($site);
            } else {
                $qb->andSiteIs($this->get('bbapp.site_context')->getSite());
            }

            if ($request->query->has('root')) {
                $qb->andParentIs(null);
            }

            $qb->addMultipleOrderBy($this->getOrderCriteria($request->query->get('order_by', null)));
        }

        if ($request->query->has('has_children')) {
            $qb->andIsSection();
            $qb->andWhere($qb->getSectionAlias().'._has_children = 1');
        }

        if (null !== $state = $request->query->get('state', null)) {
            $qb->andStateIsIn((array) $state);
        }

        if (null !== $title = $request->query->get('title', null)) {
            $qb->andWhere($qb->expr()->like($qb->getAlias().'._title', $qb->expr()->literal('%'.$title.'%')));
        }

        if (null !== $layout = $request->query->get('layout_uid', null)) {
            $qb->andWhere($qb->getAlias().'._layout = :layout')->setParameter('layout', $layout);
        }

        if (null !== $createdBefore = $request->query->get('created_before', null)) {
            $qb->andWhere($qb->getAlias().'._created > :created_before')->setParameter('created_before', $createdBefore);
        }

        if (null !== $createdAfter = $request->query->get('created_after', null)) {
            $qb->andWhere($qb->getAlias().'._created < :created_after')->setParameter('created_after', $createdAfter);
        }

        if (null !== $modifiedBefore = $request->query->get('modified_before', null)) {
            $qb->andWhere($qb->getAlias().'._modified > :modified_before')->setParameter('modified_before', $modifiedBefore);
        }

        if (null !== $modifiedAfter = $request->query->get('modified_after', null)) {
            $qb->andWhere($qb->getAlias().'._modified < :modified_after')->setParameter('modified_after', $modifiedAfter);
        }

        return $this->paginateClassicCollectionAction($qb, $start, $count);
    }

    /**
     * Getter for page entity repository.
     *
     * @return \BackBee\NestedNode\Site\Site
    */
    private function getSiteRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository('BackBee\CoreDomain\Site\Site');
    }

    /**
     * Computes order criteria for collection.
     *
     * @param  array|null $requestedOrder
     *
     * @return array
     */
    private function getOrderCriteria(array $requestedOrder = null)
    {
        if (!empty($requestedOrder)) {
            $orderBy = [];
            foreach ($requestedOrder as $key => $value) {
                if ('_' !== $key[0]) {
                    $key = '_' . $key;
                }

                $orderBy[$key] = $value;
            }
        } else {
            $orderBy = [
                '_position' => 'ASC',
                '_leftnode' => 'ASC',
            ];
        }

        return $orderBy;
    }

    private function paginateClassicCollectionAction($qb, $start, $count)
    {
        $results = new Paginator($qb->setFirstResult($start)->setMaxResults($count));
        $count = 0;
        foreach ($results as $row) {
            $count++;
        }

        $result_count = $start + $count - 1; // minus 1 because $start starts at 0 and not at 1
        $response = $this->createResponse($this->formatCollection($results));
        if (0 < $count) {
            $response->headers->set('Content-Range', "$start-$result_count/".count($results));
        }

        return $response;
    }

    /**
     * Page workflow state setter.
     *
     * @param Page  $page
     * @param State $workflow
     */
    private function trySetPageWorkflowState(Page $page, State $workflow = null)
    {
        $page->setWorkflowState(null);
        if (null !== $workflow) {
            if (null === $workflow->getLayout() || $workflow->getLayout()->getUid() === $page->getLayout()->getUid()) {
                $page->setWorkflowState($workflow);
            }
        }
    }

    /**
     * Custom patch process for Page's state property.
     *
     * @param Page  $page
     * @param array $operations passed by reference
     */
    private function patchStateOperation(Page $page, array &$operations)
    {
        $stateOp = null;
        $isHiddenOp = null;
        foreach ($operations as $key => $operation) {
            $op = [
                'key' => $key,
                'op' => $operation
            ];
            if ('/state' === $operation['path']) {
                $stateOp = $op;
            } elseif ('/is_hidden' === $operation['path']) {
                $isHiddenOp = $op;
            }
        }

        if ($page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
        }

        if (null !== $stateOp) {
            unset($operations[$stateOp['key']]);
            $states = explode('_', $stateOp['op']['value']);
            if (in_array($state = (int) array_shift($states), Page::$STATES)) {
                $page->setState($state | ($page->getState() & Page::STATE_HIDDEN ? Page::STATE_HIDDEN : 0));
            }

            if ($code = (int) array_shift($states)) {
                $workflowState = $this->getDoctrine()->getManager()
                    ->getRepository('BackBee\CoreDomain\Workflow\State')
                    ->findOneBy([
                        '_code'   => $code,
                        '_layout' => $page->getLayout(),
                    ])
                ;

                if (null !== $workflowState) {
                    $page->setWorkflowState($workflowState);
                }
            }
        }

        if (null !== $isHiddenOp) {
            unset($operations[$isHiddenOp['key']]);

            $isHidden = (boolean) $isHiddenOp['op']['value'];
            if ($isHidden && !($page->getState() & Page::STATE_HIDDEN)) {
                $page->setState($page->getState() | Page::STATE_HIDDEN);
            } elseif (!$isHidden && ($page->getState() & Page::STATE_HIDDEN)) {
                $page->setState($page->getState() ^ Page::STATE_HIDDEN);
            }
        }
    }

    /**
     * Custom patch process for Page's sibling or parent node.
     *
     * @param Page  $page
     * @param array $operations passed by reference
     */
    private function patchSiblingAndParentOperation(Page $page, array &$operations)
    {
        $sibling_operation = null;
        $parent_operation = null;
        foreach ($operations as $key => $operation) {
            $op = array('key' => $key, 'op' => $operation);
            if ('/sibling_uid' === $operation['path']) {
                $sibling_operation = $op;
            } elseif ('/parent_uid' === $operation['path']) {
                $parent_operation = $op;
            }
        }

        if (null !== $sibling_operation || null !== $parent_operation) {
            if ($page->isRoot()) {
                throw new BadRequestHttpException('Cannot move root node of a site.');
            }

            if ($page->isOnline(true)) {
                $this->granted('PUBLISH', $page); // user must have publish permission on the page
            }
        }

        try {
            if (null !== $sibling_operation) {
                unset($operations[$sibling_operation['key']]);

                $sibling = $this->getPageByUid($sibling_operation['op']['value']);
                $this->granted('EDIT', $sibling->getParent());

                $this->getPageRepository()->moveAsPrevSiblingOf($page, $sibling);
            } elseif (null !== $parent_operation) {
                unset($operations[$parent_operation['key']]);

                $parent = $this->getPageByUid($parent_operation['op']['value']);
                if ($this->isFinal($parent)) {
                    throw new BadRequestHttpException('Can\'t create children of ' . $parent->getLayout()->getLabel() . ' layout');
                }

                $this->moveAsFirstChildOf($page, $parent);
            }
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException('Invalid node move action: '.$e->getMessage());
        }
    }

    /**
     * Moves $page as first child of $parent
     *
     * @param Page      $page
     * @param Page|null $parent
     *
     * @throws BadRequestHttpException Raises if $parent is null
     */
    private function moveAsFirstChildOf(Page $page, Page $parent = null)
    {
        if (null === $parent) {
            throw new BadRequestHttpException('Parent uid doesn\'t exists');
        }

        $this->granted('EDIT', $parent);

        if (!$parent->hasMainSection()) {
            $this->getPageRepository()->saveWithSection($parent);
        }

        $this->getPageRepository()->moveAsFirstChildOf($page, $parent);
    }

    /**
     * Retrieves page entity with provided uid.
     *
     * @param string $uid
     *
     * @return Page
     *
     * @throws NotFoundHttpException raised if page not found with provided uid
     */
    private function getPageByUid($uid)
    {
        if (null === $page = $this->getDoctrine()->getManager()->find('BackBee\CoreDomain\NestedNode\Page', $uid)) {
            throw new NotFoundHttpException("Unable to find page with uid `$uid`");
        }

        return $page;
    }
}
