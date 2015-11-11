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

namespace BackBee\WebBundle\Renderer;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\Renderer\Exception\RendererException;
use BackBee\CoreDomain\Renderer\RenderableInterface;
use BackBee\Routing\RouteCollection;
use BackBee\CoreDomain\Site\Layout;
use BackBee\CoreDomain\Site\Site;
use BackBee\Utils\File\File;
use BackBee\Utils\StringUtils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Renderer engine class; able to manage multiple template engine.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Renderer extends AbstractRenderer
{
    /**
     * constants used to manage external resources.
     */
    const CSS_LINK = 'css';
    const HEADER_JS = 'js_header';
    const FOOTER_JS = 'js_footer';

    /**
     * Contains every RendererAdapterInterface added by user
     * @var ParameterBag
     */
    private $rendererAdapters;

    /**
     * Contains every extensions that Renderer can manage thanks to registered RendererAdapterInterface
     * @var ParameterBag
     */
    private $manageableExt;

    /**
     * key of the default adapter to use when there is a conflict.
     *
     * @var string
     */
    private $defaultAdapter;

    /**
     * The file path to the template.
     *
     * @var string
     */
    private $templateFile;

    /**
     * define if renderer has been restored by container or not.
     *
     * @var boolean
     */
    private $isRestored;

    /**
     * contains every external resources of current page (js and css).
     *
     * @var ParameterBag
     */
    private $externalResources;
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * Constructor.
     *
     * @param BBApplication $application
     * @param array|null    $config
     * @param boolean       $autoloadRendererApdater
     */
    public function __construct(TwigEngine $twig, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($eventDispatcher, $entityManager);
        $this->twig = $twig;
        // It is only used in the metadata helper, so this needs to be refactored!
    }

    /**
     * Update every helpers and every registered renderer adapters with the right AbstractRenderer;
     * this method is called everytime we clone a renderer
     */
    public function updatesAfterClone()
    {
        $this->updateHelpers();
//        foreach ($this->rendererAdapters->all() as $ra) {
//            $ra->onNewRenderer($this);
//        }

        return $this;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Register a renderer adapter ($rendererAdapter); this method also set
     * current $rendererAdapter as default adapter if it is not set.
     *
     * @param RendererAdapterInterface $rendererAdapter
     */
    public function addRendererAdapter(RendererAdapterInterface $rendererAdapter)
    {
        $key = $this->getRendererAdapterKey($rendererAdapter);
        if (!$this->rendererAdapters->has($key)) {
            $this->rendererAdapters->set($key, $rendererAdapter);
            $this->addManagedExtensions($rendererAdapter);
        }

        if (null === $this->defaultAdapter) {
            $this->defaultAdapter = $key;
        }
    }

    /**
     * @param string $ext
     *
     * @return RendererAdapterInterface
     */
// @todo gvf lo usa el data collector, creo que para el profiler, pero no lo necesitaremos
//    public function getAdapterByExt($ext)
//    {
//        if (null === $adapter = $this->determineWhichAdapterToUse('.'.$ext)) {
//            throw new RendererException("Unable to find adapter for '.$ext'.", RendererException::SCRIPTFILE_ERROR);
//        }
//
//        return $adapter;
//    }

    /**
     * Set the adapter referenced by $adapterKey as defaultAdapter to use in conflict
     * case; the default adapter is also considered by self::getRightAdapter().
     *
     * @param string $adapterKey
     *
     * @return boolean
     */
    public function defaultAdapter($adapterKey)
    {
        $exists = false;
        if (in_array($adapterKey, $this->rendererAdapters->keys())) {
            $this->defaultAdapter = $adapterKey;
            $exists = true;
        }

        return $exists;
    }

    /**
     * Return template file extension of the default adapter.
     *
     * @return String
     */
//    no se usa
//    public function getDefaultAdapterExt()
//    {
//        $managedExt = $this->rendererAdapters->get($this->defaultAdapter)->getManagedFileExtensions();
//
//        return array_shift($managedExt);
//    }

    /**
     * Getters of renderer adapter by $key.
     *
     * @return BackBee\Renderer\RendererAdapterInterface
     */
//    @TODO gvf no se usa
//    public function getAdapter($key)
//    {
//        return $this->rendererAdapters->get($key);
//    }

    /**
     * Getters of renderer adapters.
     *
     * @return array<BackBee\Renderer\RendererAdapterInterface>
     */
//   @TODO gvf no se usa
//    public function getAdapters()
//    {
//        return $this->rendererAdapters->all();
//    }

    /**
     * @see BackBee\Renderer\RendererInterface::render()
     */
    public function render(RenderableInterface $obj = null, $mode = null, $params = null, $template = null, $ignoreModeIfNotSet = false)
    {
        if (null === $obj) {
            return;
        }

        $application = $this->getApplication();
        if (!$obj->isRenderable() && null === $application->getBBUserToken()) {
            return;
        }

//        @TODO gvf
//        $application->debug(sprintf(
//            'Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).',
//            get_class($obj),
//            $obj->getUid(),
//            $mode,
//            $ignoreModeIfNotSet
//        ));

        $renderer = clone $this;

        $renderer->updatesAfterClone();

        $this->setRenderParams($renderer, $params);

        $renderer
            ->setObject($obj)
            ->setMode($mode, $ignoreModeIfNotSet)
            ->triggerEvent('prerender')
        ;

        if (null === $renderer->__render) {
            // Rendering a page with layout
            if ($obj instanceof Page) {
                $renderer->setCurrentPage($obj);
                $renderer->__render = $renderer->renderPage($template, $params);
//                @TODO gvf
//                $renderer->insertExternalResources();
//                $application->debug('Rendering Page OK');
            } else {
                // Rendering a content
                $renderer->__render = $renderer->renderContent($params, $template);
            }

            $renderer->triggerEvent('postrender', null, $renderer->__render);
        }

        $render = $renderer->__render;
        unset($renderer);

        $this->updatesAfterUnset();

        return $render;
    }

    public function tryResolveParentObject(AbstractClassContent $parent, AbstractClassContent $element)
    {
        foreach ($parent->getData() as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }

            foreach ($values as $value) {
                if ($value instanceof AbstractClassContent) {
                    if (!$value->isLoaded()) {
                        // try to load subcontent
                        if (null !== $subcontent = $this->getApplication()
                                ->getEntityManager()
                                ->getRepository(\Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($value))
                                ->load($value, $this->getRenderer()->getApplication()->getBBUserToken())) {
                            $value = $subcontent;
                        }
                    }

                    if ($element->equals($value)) {
                        $this->__currentelement = $key;
                        $this->__object = $parent;
                        $this->_parentuid = $parent->getUid();
                    } else {
                        $this->tryResolveParentObject($value, $element);
                    }
                }

                if (null !== $this->__currentelement) {
                    break;
                }
            }

            if (null !== $this->__currentelement) {
                break;
            }
        }
    }

    /**
     * @see BackBee\Renderer\RendererInterface::partial()
     */
    public function partial($template = null, $params = null)
    {
        $this->templateFile = $template;

        // Assign parameters
        if (null !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value) {
                $this->setParam($param, $value);
            }
        }

        return $this->renderTemplate(true);
    }

    /**
     * @see BackBee\Renderer\RendererInterface::error()
     */
    public function error($errorCode, $title = null, $message = null, $trace = null)
    {
        $found = false;
        foreach ($this->manageableExt->keys() as $ext) {
            $this->templateFile = 'error'.DIRECTORY_SEPARATOR.$errorCode.$ext;
            if (true === $this->isValidTemplateFile($this->templateFile, true)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            foreach ($this->manageableExt->keys() as $ext) {
                $this->templateFile = 'error'.DIRECTORY_SEPARATOR.'default'.$ext;
                if (true === $this->isValidTemplateFile($this->templateFile)) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            return false;
        }

        $this->assign('error_code', $errorCode);
        $this->assign('error_title', $title);
        $this->assign('error_message', $message);
        $this->assign('error_trace', $trace);

        return $this->renderTemplate(false, true);
    }

    /**
     * Check if $filename exists.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function isTemplateFileExists($filename)
    {
        return $this->isValidTemplateFile($filename);
    }

    /**
     * Returns image url.
     *
     * @param string            $pathinfo
     * @param BackBee\CoreDomain\Site\Site $site
     *
     * @return string image url
     */
    public function getImageUrl($pathinfo, Site $site = null)
    {
        return $this->getUri($pathinfo, null, $site, RouteCollection::IMAGE_URL);
    }

    /**
     * Returns image url.
     *
     * @param string            $pathinfo
     * @param BackBee\CoreDomain\Site\Site $site
     *
     * @return string image url
     */
    public function getMediaUrl($pathinfo, Site $site = null)
    {
        return $this->getUri($pathinfo, null, $site, RouteCollection::MEDIA_URL);
    }

    /**
     * Returns resource url.
     *
     * @param string            $pathinfo
     * @param BackBee\CoreDomain\Site\Site $site
     *
     * @return string resource url
     */
    public function getResourceUrl($pathinfo, Site $site = null)
    {
//        return $this->getUri($pathinfo, null, $site, RouteCollection::RESOURCE_URL);
        return $this->getUri($pathinfo, null, $site);
    }

    /**
     * Compute route which matched with routeName and replace every token by its values specified in routeParams;
     * You can also give base url (by default current site base url will be used).
     *
     * @param string      $routeName
     * @param array|null  $routeParams
     * @param string|null $baseUrl
     * @param boolean     $addExt
     * @param  \BackBee\CoreDomain\Site\Site
     *
     * @return string
     */
    public function generateUrlByRouteName($routeName, array $routeParams = null, $baseUrl = null, $addExt = true, Site $site = null, $buildQuery = false)
    {
        return $this->application->getRouting()->getUrlByRouteName($routeName, $routeParams, $baseUrl, $addExt, $site, $buildQuery);
    }

    /**
     * Returns an array of template files according the provided pattern.
     *
     * @param string $pattern
     *
     * @return array
     */
    public function getTemplatesByPattern($pattern)
    {
        $templates = array();
        foreach ($this->manageableExt->keys() as $ext) {
            $templates = array_merge($templates, parent::getTemplatesByPattern($pattern.$ext));
        }

        return $templates;
    }

    /**
     * Returns the list of available render mode for the provided object.
     *
     * @param  \BackBee\Renderer\RenderableInterface $object
     * @return array
     */
    public function getAvailableRenderMode(RenderableInterface $object)
    {
        $modes = parent::getAvailableRenderMode($object);
        foreach ($modes as &$mode) {
            $mode = str_replace($this->manageableExt->keys(), '', $mode);
        }

        return array_unique($modes);
    }

    /**
     * @see BackBee\Renderer\RendererInterface::updateLayout()
     */
    public function updateLayout(Layout $layout)
    {
        $layoutFile = parent::updateLayout($layout);
        $adapter = $this->determineWhichAdapterToUse($layoutFile);

        if (!is_array($this->_layoutdir) || 0 === count($this->_layoutdir)) {
            throw new RendererException('None layout directory defined', RendererException::SCRIPTFILE_ERROR);
        }

        if (null === $adapter) {
            throw new RendererException(sprintf(
                'Unable to manage file \'%s\' in path (%s)', $layoutFile, $this->_layoutdir[0]
            ), RendererException::SCRIPTFILE_ERROR);
        }

        return $adapter->updateLayout($layout, $layoutFile);
    }

    /**
     * Adds provided href as stylesheet to add to current page head tag
     * Note: provided href will be added only if it does not already exist in stylesheet list.
     *
     * @param string $href
     */
    public function addStylesheet($href)
    {
        $this->addExternalResources(self::CSS_LINK, $href);
    }

    /**
     * Adds provided href as javascript script to add to current page head tag
     * Note: provided href will be added only if it does not already exist in javascript script list.
     *
     * @param string $href
     */
    public function addHeaderJs($href)
    {
        $this->addExternalResources(self::HEADER_JS, $href);
    }

    /**
     * Adds provided href as javascript script to add to current page footer
     * Note: provided href will be added only if it does not already exist in javascript script list.
     *
     * @param string $href
     */
    public function addFooterJs($href)
    {
        $this->addExternalResources(self::FOOTER_JS, $href);
    }

    /**
     * Alias to self::addHeaderJs().
     *
     * @deprecated since version 0.12
     */
    public function addHeaderScript($src)
    {
        $this->addHeaderJs($src);
    }

    /**
     * Alias to self::addFooterJs().
     *
     * @deprecated since version 0.12
     */
    public function addFooterScript($src)
    {
        $this->addFooterJs($src);
    }

    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required.
     *
     * @return string|null the namespace of the class proxy to use on restore or null if no proxy required
     */
    public function getClassProxy()
    {
        return;
    }

    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method.
     *
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump(array $options = array())
    {
        return array(
            'template_directories' => $this->_scriptdir,
            'layout_directories'   => $this->_layoutdir,
        );
    }

    /**
     * Restore current service to the dump's state.
     *
     * @param array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                    restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->_scriptdir = $dump['template_directories'];
        $this->_layoutdir = $dump['layout_directories'];

        $this->isRestored = true;
    }

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->isRestored;
    }

    /**
     * Return the file path to current layout, try to create it if not exists.
     *
     * @param Layout $layout
     *
     * @return string the file path
     *
     * @throws RendererException
     */
    protected function getLayoutFile(Layout $layout)
    {
        $layoutfile = $layout->getPath();
        if (null === $layoutfile && 0 < $this->manageableExt->count()) {
            $adapter = null;
            if (null !== $this->defaultAdapter && null !== $adapter = $this->rendererAdapters->get($this->defaultAdapter)) {
                $extensions = $adapter->getManagedFileExtensions();
            } else {
                $extensions = $this->manageableExt->keys();
            }

            if (0 === count($extensions)) {
                throw new RendererException(
                        'Declared adapter(s) (count:'.$this->rendererAdapters->count().') is/are not able to manage '.
                        'any file extensions at moment.'
                );
            }

            $layoutfile = StringUtils::toPath($layout->getLabel(), array('extension' => reset($extensions)));
            $layout->setPath($layoutfile);
        }

        return $layoutfile;
    }

    /**
     * Update every helpers and every registered renderer adapters with the right AbstractRenderer;
     * this method is called everytime we unset a renderer
     */
    protected function updatesAfterUnset()
    {
        $this->updateHelpers();
//        foreach ($this->rendererAdapters->all() as $ra) {
//            $ra->onRestorePreviousRenderer($this);
//        }

        return $this;
    }

    /**
     * Autoloads every declared renderer adapters into application config.
     *
     * @return self
     */
    private function autoloadAdapters()
    {
        $rendererConfig = $this->getApplication()->getConfig()->getRendererConfig();
        $adapters = (array) $rendererConfig['adapter'];
        foreach ($adapters as $adapter) {
            $classname = $adapter;
            $adapterConfig = [];
            if (is_array($adapter)) {
                $classname = isset($adapter['class']) ? $adapter['class'] : null;
                $adapterConfig = isset($adapter['config']) && is_array($adapter['config']) ? $adapter['config'] : [];
            }

            $this->addRendererAdapter(new $classname($this, $adapterConfig));
        }

        return $this;
    }

    /**
     * Generic method to add an external resource (css, javascript in page header or footer).
     *
     * @param string $type
     * @param string $href
     */
    private function addExternalResources($type, $href)
    {
        $resources = array();
        if ($this->externalResources->has($type)) {
            $resources = $this->externalResources->get($type);
        }

        if (!in_array($href, $resources)) {
            $resources[] = $href;
        }

        $this->externalResources->set($type, $resources);
    }

    /**
     * Insert every external resources: css and header js will be added before page '</head>' and
     * footer javascript will be added before page '</body>'.
     *
     * @return self
     */
    private function insertExternalResources()
    {
        $header_render = '';
        foreach ($this->externalResources->get(self::CSS_LINK, array()) as $href) {
            $header_render .= $this->generateStylesheetTag($href);
        }

        foreach ($header_js = $this->externalResources->get(self::HEADER_JS, array()) as $src) {
            $header_render .= $this->generateJavascriptTag($src);
        }

        if (!empty($header_render)) {
            $this->setRender(str_replace('</head>', "$header_render</head>", $this->getRender()));
        }

        $footer_render = '';
        $footer_js = array_diff($this->externalResources->get(self::FOOTER_JS, array()), $header_js);
        foreach ($footer_js as $src) {
            $footer_render .= $this->generateJavascriptTag($src);
        }

        if (!empty($footer_render)) {
            $this->setRender(str_replace('</body>', "$footer_render</body>", $this->getRender()));
        }

        $this->externalResources->remove(self::CSS_LINK);
        $this->externalResources->remove(self::HEADER_JS);
        $this->externalResources->remove(self::FOOTER_JS);

        return $this;
    }

    /**
     * Generates HTML5 link tag with provided href.
     *
     * @param string $href
     *
     * @return string
     */
    private function generateStylesheetTag($href)
    {
        return '<link rel="stylesheet" href="'.$href.'" type="text/css">';
    }

    /**
     * Generates HTML5 script tag with provided src.
     *
     * @param string $src
     *
     * @return string
     */
    private function generateJavascriptTag($src)
    {
        return '<script src="'.$src.'"></script>';
    }

    /**
     * Compute a key for renderer adapter ($rendererAdapter).
     *
     * @param  RendererAdapterInterface $rendererAdapter
     * @return string
     */
    private function getRendererAdapterKey(RendererAdapterInterface $rendererAdapter)
    {
        $key = explode(NAMESPACE_SEPARATOR, get_class($rendererAdapter));

        return strtolower($key[count($key) - 1]);
    }

    /**
     * Extract managed extensions from rendererAdapter and store it.
     *
     * @param RendererAdapterInterface $rendererAdapter
     */
    private function addManagedExtensions(RendererAdapterInterface $rendererAdapter)
    {
        $key = $this->getRendererAdapterKey($rendererAdapter);
        foreach ($rendererAdapter->getManagedFileExtensions() as $ext) {
            $rendererAdapters = array($key);
            if ($this->manageableExt->has($ext)) {
                $rendererAdapters = $this->manageableExt->get($ext);
                $rendererAdapters[] = $key;
            }

            $this->manageableExt->set($ext, $rendererAdapters);
        }
    }

    /**
     * Returns an adapter containing in $adapeters; it will returns in prior
     * the defaultAdpater if it is in $adapters or the first adapter found.
     *
     * @param array $adapters contains object of type IRendererAdapter
     *
     * @return RendererAdapterInterface
     */
    private function getRightAdapter(array $adapters)
    {
        $adapter = null;
        if (1 < count($adapters) && in_array($this->defaultAdapter, $adapters)) {
            $adapter = $this->defaultAdapter;
        } else {
            $adapter = reset($adapters);
        }

        return $adapter;
    }

    /**
     * Returns the right adapter to use according to the filename extension.
     *
     * @return RendererAdapterInterface
     */
    private function determineWhichAdapterToUse($filename = null)
    {
        if (null === $filename || !is_string($filename)) {
            return;
        }

        $pieces = explode('.', $filename);
        if (1 > count($pieces)) {
            return;
        }

        $ext = '.'.$pieces[count($pieces) - 1];
        $adaptersForExt = $this->manageableExt->get($ext);
        if (!is_array($adaptersForExt) || 0 === count($adaptersForExt)) {
            return;
        }

        $adapter = $this->getRightAdapter($adaptersForExt);

        return $this->rendererAdapters->get($adapter);
    }

    /**
     * Render a page object.
     *
     * @param string $layoutfile A force layout script to be rendered
     *
     * @return string The rendered output
     *
     * @throws RendererException
     */
    private function renderPage($layoutFile = null, $params = null)
    {
        $this->setNode($this->getObject());

        $application = $this->getApplication();
        // Rendering subcontent
        if (null !== $contentSet = $this->getObject()->getContentSet()) {
//            @TODO gvf
//            $bbUserToken = $application->getBBUserToken();
//            $revisionRepo = $application->getEntityManager()->getRepository('BackBee\CoreDomain\ClassContent\Revision');
//            if (null !== $bbUserToken && null !== $revision = $revisionRepo->getDraft($contentSet, $bbUserToken)) {
//                $contentSet->setDraft($revision);
//            }

            $layout = $this->getObject()->getLayout();
            $zones = $layout->getZones();
            $zoneIndex = 0;
            $b= $contentSet->getData();
            foreach ($b as $content) {
                if (array_key_exists($zoneIndex, $zones)) {
                    $zone = $zones[$zoneIndex];
                    $isMain = null !== $zone && property_exists($zone, 'mainZone') && true === $zone->mainZone;
                    $a = $this->container();
                    $c= $this->render($content, $this->getMode(), array(
                        'class' => 'rootContentSet',
                        'isRoot' => true,
                        'indexZone' => $zoneIndex++,
                        'isMainZone' => $isMain,
                    ), null, $this->_ignoreIfRenderModeNotAvailable);
//                    @TODO gvf
                    $b= $a->add($c);
                }
            }
        }

        // Check for a valid layout file
        $this->templateFile = $layoutFile;
        if (null === $this->templateFile) {
            $this->templateFile = $this->getLayoutFile($this->getCurrentPage()->getLayout());
        }
//        @todo gvf
//        @TODO GVF
//        if (!$this->isValidTemplateFile($this->templateFile, true)) {
//            throw new RendererException(
//                sprintf('Unable to read layout %s.', $this->templateFile), RendererException::LAYOUT_ERROR
//            );
//        }
// @TODO gvf
//        $application->info(sprintf('Rendering page `%s`.', $this->getObject()->getNormalizeUri()));

        return $this->renderTemplate(false, true);
    }

    /**
     * Render a ClassContent object.
     *
     * @param array  $params   A Force set of parameters to render the object
     * @param string $template A force template script to be rendered
     *
     * @return string The rendered output
     *
     * @throws RendererException
     */
    private function renderContent($params = null, $template = null)
    {
        try {
            $mode = null !== $this->getMode() ? $this->getMode() : $this->_object->getMode();
            $this->templateFile = $template;
            if (null === $this->templateFile && null !== $this->_object) {
                $this->templateFile = $this->getTemplateFile($this->_object, $mode);
                // Aqui entra solo si se activa el standard bundle
                if (false === $this->templateFile) {
                    $this->templateFile = $this->getTemplateFile($this->_object, $this->getMode());
                }

                if (false === $this->templateFile && false === $this->_ignoreIfRenderModeNotAvailable) {
                    $this->templateFile = $this->getTemplateFile($this->_object);
                }
            }

//            if (false === $this->isValidTemplateFile($this->templateFile)) {
//                throw new RendererException(sprintf(
//                        'Unable to find file \'%s\' in path (%s)', $template, implode(', ', $this->_scriptdir)
//                ), RendererException::SCRIPTFILE_ERROR);
//            }
        } catch (RendererException $e) {
            $render = '';

            // Unknown template, try to render subcontent
            if (null !== $this->_object && is_array($this->_object->getData())) {
                foreach ($this->_object->getData() as $subcontents) {
                    $subcontents = (array) $subcontents;

                    foreach ($subcontents as $sc) {
                        if ($sc instanceof RenderableInterface) {
                            $scRender = $this->render(
                                $sc, $this->getMode(), $params, $template, $this->_ignoreIfRenderModeNotAvailable
                            );

                            if (false === $scRender) {
                                throw $e;
                            }

                            $render .= $scRender;
                        }
                    }
                }
            }

            return $render;
        }

        // Assign vars and parameters
        if (null !== $this->_object) {
            $draft = $this->_object->getDraft();
            $aClassContentClassname = 'BackBee\CoreDomain\ClassContent\AbstractClassContent';
            if ($this->_object instanceof $aClassContentClassname && !$this->_object->isLoaded()) {
                // trying to refresh unloaded content
                $em = $this->getEntityManager();

                $classname = get_class($this->_object);
                $uid = $this->_object->getUid();

                $em->detach($this->_object);
                $object = $em->find($classname, $uid);
                if (null !== $object) {
                    $this->_object = $object;
                    if (null !== $draft) {
                        $this->_object->setDraft($draft);
                    }
                }
            }

            $this->assign($this->_object->getData());
            $this->setParam($this->_object->getAllParams());
        }

//        if (null !== $application) {
//            $application->debug(sprintf(
//                'Rendering content `%s(%s)`.',
//                get_class($this->_object),
//                $this->_object->getUid()
//            ));
//        }

        return $this->renderTemplate();
    }

    /**
     * Set parameters to a renderer object in parameter.
     *
     * @param AbstractRenderer $render
     * @param array     $params
     */
    private function setRenderParams(AbstractRenderer $render, $params)
    {
        if (null !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value) {
                $render->setParam($param, $value);
            }
        }
    }

    /**
     * Try to compute and guess a valid filename for $object:
     * 		- on success return string which is the right filename with its extension
     * 		- on fail return false.
     *
     * @param  RenderableInterface    $object
     * @param  string         $mode
     * @return string|boolean string if successfully found a valid file name, else false
     */
//    @gvf this is used only when activating the standard bundle
    private function getTemplateFile(RenderableInterface $object, $mode = null)
    {
//        $tmpStorage = $this->templateFile;
        $this->templateFile = strtolower($this->getTemplatePath($object).'.twig');
//        foreach ($this->manageableExt->keys() as $ext) {
//            $this->templateFile = $template.(null !== $mode ? '.'.$mode : '').$ext;
//            if ($this->isValidTemplateFile($this->templateFile)) {
//                $filename = $this->templateFile;
//                $this->templateFile = $tmpStorage;

                return $this->templateFile ;
//            }
//        }

//        @todo gvf esto me imagino que busca el parent template si no hay uno especifico
        if ($parentClassname = get_parent_class($object)) {
            $parent = new \ReflectionClass($parentClassname);
            if (!$parent->isAbstract()) {
                return $this->getTemplateFile(new $parentClassname(), $mode, null);
            }
        }

        return false;
    }

    /**
     * Use the right adapter depending on $filename extension to define if
     * $filename is a valid template filename or not.
     *
     * @param string  $filename
     * @param boolean $isLayout if you want to check $filename in layout dir, default: false
     *
     * @return boolean
     */
    private function isValidTemplateFile($filename, $isLayout = false)
    {
        $adapter = $this->determineWhichAdapterToUse($filename);
        if (null === $adapter) {
            return false;
        }

        return $adapter->isValidTemplateFile(
            $filename, true === $isLayout ? $this->_layoutdir : $this->_scriptdir
        );
    }

    /**
     * @param boolean $isPartial
     * @param boolean $isLayout
     *
     * @return string
     */
    private function renderTemplate($isPartial = false, $isLayout = false)
    {
// @TODO gvf
//        $this->getApplication()->debug(sprintf('Rendering file `%s`.', $this->templateFile));
        if (false === $isPartial) {
            $this->triggerEvent();
        }

//        dirty workaround
        if (0!==strpos($this->templateFile,'BackBeeWebBundle')){

        $this->templateFile = 'BackBeeWebBundle::'. strtolower(str_replace('.twig', '.html.twig', $this->templateFile));
        }

        $x = $this->getObject();
        $y = $x->getData();

        $a =  $this->twig->render(
            $this->templateFile,
            array_merge($this->getAssignedVars(), $this->getBBVariable(), $this->getParam(),['this'=> $this])
        );

        return $a;
    }

    /**
     * Returns default parameters that are availables in every templates.
     *
     * @return array
     */
    private function getBBVariable()
    {
        return [];
//        @todo gvf make globally available, some already are
        return [
            'bb' => [
                'debug'      => $this->getApplication()->isDebugMode(),
                'token'      => $this->getApplication()->getBBUserToken(),
                'request'    => $this->getApplication()->getRequest(),
                'routing'    => $this->getApplication()->getRouting(),
                'translator' => $this->getApplication()->getContainer()->get('translator'),
            ],
        ];
    }
}
