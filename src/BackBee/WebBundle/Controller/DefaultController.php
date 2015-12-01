<?php

namespace BackBee\WebBundle\Controller;

use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomainBundle\Event\PageFilterEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author Gonzalo Vilaseca <gvf.vilaseca@reiss.com>
 */
class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('BackBeeWebBundle::Home.html.twig', array());
    }

    /**
     * Handles the request when none other action was found.
     *
     * @access public
     *
     * @param string $uri The URI to handle
     *
     * @throws FrontControllerException
     */
    public function defaultAction(Request $request, $sendResponse = true)
    {
        if (!$site = $this->get('bbapp.site_context')->getSite()) {
            throw new HttpException(500, 'A BackBee\CoreDomain\Site instance is required.');
        }

        $redirect_page = null !== $request->get('bb5-redirect', null)
            ? ('false' !== $request->get('bb5-redirect'))
            : true
        ;

        // Attributes are populated by the Dynamic Router
        $page = $request->attributes->get('_bbapp_page');

        $role = $this->container->getParameter('bbapp.api_user_role');

        // The page is not active, but we are logged in, so we should be able to open it for edit
        // @todo this should be done differently?
        if (null !== $page && false === $page->isOnline()) {
//             @todo gvf the role should be parameter or something more configurable
            $page = $this->isGranted($role) ? $page: null;
        }

        if (null === $page) {
            throw new HttpException(404, sprintf('The URL `%s` can not be found.',  $request->getUri()));
        }
        if ((null !== $redirect = $page->getRedirect()) && $page->getUseUrlRedirect()) {
            if ((!$this->isGranted($role)) || (($this->isGranted($this->container->getParameter('bbapp.api_user_role'))) && (true === $redirect_page))) {
                $redirect = $this->get('renderer')->getUri($redirect);

                return new RedirectResponse($redirect, 301, [
                        'Cache-Control' => 'no-store, no-cache, must-revalidate',
                        'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
                    ]
                );

            }
        }


//    @TODO gvf I don't think this event is used anywhere
        $event = new PageFilterEvent($page);
        $this->get('event_dispatcher')->dispatch('application.page', $event);
// @TODO gvf not sure what this bb5-mode is

//        if (null !== $this->getRequest()->get('bb5-mode')) {
//            $response = new Response($this->application->getRenderer()->render($page, $this->getRequest()->get('bb5-mode')));
//        } else {
        $b =
            $response = new Response($this->get('renderer')->render($page));
//        }

//        if ($sendResponse) {
//            return $response->send();
//        } else {
            return $response;
//        }
    }
}
