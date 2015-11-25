<?php
/**
 * @author    Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 * @date      25/11/2015
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\CoreDomainBundle\Routing;

use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomainBundle\Site\SiteContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class RouteProvider implements RouteProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var SiteContext
     */
    private $siteContext;

    public function __construct(
        EntityManagerInterface $entityManager,
        SiteContext $siteContext
    )
    {

        $this->entityManager = $entityManager;
        $this->siteContext   = $siteContext;
    }

    /* If the path matches the url field of a Page we create the route for it
     *
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $path       = $request->getPathInfo();
        $collection = new RouteCollection();

        $page = $this->entityManager
            ->getRepository('BackBee\CoreDomain\NestedNode\Page')
            ->findOneBy(array(
                '_site'  => $this->siteContext->getSite(),
                '_url'   => $path,
                '_state' => Page::getUndeletedStates(),
            ));

        if ($page) {
            $route = $this->createRouteFromEntity($page);
            $collection->add($path, $route);
        }

        return $collection;
    }

    /*
     * {@inheritdoc}
     */
    public function getRouteByName($name)
    {
        if (is_object($name)) {
//            @todo gvf Page class should be configurable or use interface
            if ($name instanceof Page) {
                return $this->createRouteFromEntity($name);
            }
        }

        $page = $this->entityManager
            ->getRepository('BackBee\CoreDomain\NestedNode\Page')
            ->findOneBy(array(
                '_site'  => $this->siteContext->getSite(),
                '_url'   => $name,
                '_state' => Page::getUndeletedStates(),
            ));

        if ($page) {
            return $this->createRouteFromEntity($page);
        }

        throw new RouteNotFoundException("No route found for name '$name'");
    }

    /*
     * {@inheritdoc}
     */
    public function getRoutesByNames($names)
    {
        if (null === $names) {

            $collection = new RouteCollection();

            $pages = $this->entityManager
                ->getRepository('BackBee\CoreDomain\NestedNode\Page')
                ->findBy(array(
                    '_site'  => $this->siteContext->getSite(),
                    '_state' => Page::getUndeletedStates(),
                ));

            foreach ($pages as $page) {
                    $name = $page->getUrl();
                    $collection->add($name, $this->createRouteFromEntity($page, $name));
            }

            return $collection;
        }

        $routes = array();
        foreach ($names as $name) {
            try {
                $routes[] = $this->getRouteByName($name);
            } catch (RouteNotFoundException $e) {
                // not found
            }
        }

        return $routes;
    }

    /**
     * @param string $entity
     * @param string $colour
     * @param string $value
     *
     * @return Route
     */
    private function createRouteFromEntity($entity, $value = null)
    {
//        @todo gvf controller should be configurable
        $defaults = array('_bbapp_page' => $entity, '_controller' => 'BackBeeWebBundle:Default:default');

        if (null === $value) {
            $value = $entity->getUrl();
        }

        return new Route($value, $defaults);
    }
}