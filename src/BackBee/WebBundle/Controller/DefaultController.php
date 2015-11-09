<?php

namespace BackBee\WebBundle\Controller;

use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomainBundle\Event\PageFilterEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
    public function defaultAction($uri = '', $sendResponse = true)
    {
        if (!$site = $this->get('bbapp.site_context')->getSite()) {
            throw new HttpException(500, 'A BackBee\Site instance is required.');
        }

        ;
// @TODO gvf
//        $redirect_page = null !== $this->application->getRequest()->get('bb5-redirect', null)
//            ? ('false' !== $this->application->getRequest()->get('bb5-redirect'))
//            : true
//        ;

        $page = $this->getDoctrine()
            ->getManager()
            ->getRepository('BackBee\CoreDomain\NestedNode\Page')
            ->findOneBy(array(
                '_site'  => $site,
                '_url'   => $uri,
                '_state' => Page::getUndeletedStates(),
            ));
// @TODO gvf
//        if (null !== $page && false === $page->isOnline()) {
//            $page = (null === $this->application->getBBUserToken()) ? null : $page;
//        }

        if (null === $page) {
            throw new HttpException(404, sprintf('The URL `%s` can not be found.',  $uri));
        }
// @TODO gvf
//        if ((null !== $redirect = $page->getRedirect()) && $page->getUseUrlRedirect()) {
//            if ((null === $this->application->getBBUserToken()) || ((null !== $this->application->getBBUserToken()) && (true === $redirect_page))) {
//                $redirect = $this->application->getRenderer()->getUri($redirect);
//
//                $response = new RedirectResponse($redirect, 301, [
//                        'Cache-Control' => 'no-store, no-cache, must-revalidate',
//                        'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
//                    ]
//                );
//
//                $this->send($response);
//                $this->application->stop();
//            }
//        }

        $this->get('logger')->info(sprintf('Handling URL request `%s`.', $uri));

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
