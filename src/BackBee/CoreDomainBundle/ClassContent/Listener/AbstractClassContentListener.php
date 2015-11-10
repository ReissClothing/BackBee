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

namespace BackBee\CoreDomainBundle\ClassContent\Listener;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomainBundle\ClassContent\Element\File as ElementFile;
use BackBee\ClassContent\Exception\ClassContentException;
use BackBee\CoreDomain\ClassContent\Revision;
use BackBee\CoreDomainBundle\Event\Event;
use BackBee\Exception\BBException;
use BackBee\Security\Exception\SecurityException;
use BackBee\Utils\File\File;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Listener to ClassContent events :
 *    - classcontent.onflush: occurs when a classcontent entity is mentioned for current flush
 *    - classcontent.include: occurs when autoloader include a classcontent definition.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractClassContentListener
{
    private $exceptionOnUnknownClassname;

//    public function __construct($exceptionOnUnknownClassname)
//    {
//
//        $this->exceptionOnUnknownClassname = strtolower($exceptionOnUnknownClassname);
//    }
    /**
     * Add discriminator values to class MetaData when a content class is loaded
     * Occur on classcontent.include events.
     *
     * @param Event $event
     */
    public function onInclude(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (null !== $dispatcher->getApplication()) {
            $em = $dispatcher->getApplication()->getEntityManager();
            $shortClassname = AbstractClassContent::getShortClassname($event->getTarget());
            $fullClassname = AbstractClassContent::getFullClassname($event->getTarget());
            foreach (class_parents($fullClassname) as $classname) {
                $em->getClassMetadata($classname)->addDiscriminatorMapClass($shortClassname, $fullClassname);

                if ('BackBee\CoreDomain\ClassContent\AbstractClassContent' === $classname) {
                    break;
                }
            }
        }
    }

    /**
     * Occurs on classcontent.postload events.
     * 
     * @param Event $event
     */
    public function postload(LifecycleEventArgs $event)
    {
        if (is_a($event->getObject(), 'BackBee\CoreDomain\ClassContent\AbstractClassContent')) {
            $event->getObject()->postLoad();
        }
    }

    /**
     * Occurs on classcontent.onflush events.
     * 
     * @param Event $event
     */
    public function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AbstractClassContent)) {
            return;
        }

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForInsert($content) || $uow->isScheduledForUpdate($content)) {
            if (null !== $content->getProperty('labelized-by')) {
                $elements = explode('->', $content->getProperty('labelized-by'));
                $element = null;
                $value = $content;
                foreach ($elements as $element) {
                    if (null !== $value) {
                        $value = $value->getData($element);
                        if ($value instanceof AbstractClassContent && false === $em->contains($value)) {
                            $value = $em->find(get_class($value), $value->getUid());
                        }
                    }
                }

                $content->setLabel($value);
            }

            if (null === $content->getLabel()) {
                $content->setLabel($content->getProperty('name'));
            }

            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($content)), $content);
        }
    }

    /**
     * @param AbstractClassContent $content
     * @param ApplicationInterface $application
     */
    public function handleContentMainnode(AbstractClassContent $content, $application)
    {
        if (!isset($content) && $content->isElementContent()) {
            return;
        }
    }

    /**
     * Occur on clascontent.preremove event.
     *
     * @param \BackBee\Event\Event $event
     *
     * @return type
     */
    public function onPreRemove(Event $event)
    {
        return;
    }

    /**
     * Occurs on classcontent.update event.
     *
     * @param Event $event
     *
     * @throws BBException Occurs on illegal targeted object or missing BackBee Application
     */
    public function onUpdate(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AbstractClassContent)) {
            throw new BBException(
                'Enable to update object',
                BBException::INVALID_ARGUMENT,
                new \InvalidArgumentException(sprintf(
                    'Only BackBee\CoreDomain\ClassContent\AbstractClassContent can be commit, `%s` received',
                    get_class($content)
                ))
            );
        }

        $dispatcher = $event->getDispatcher();
        if (null === $application = $dispatcher->getApplication()) {
            throw new BBException(
                'Enable to update object',
                BBException::MISSING_APPLICATION,
                new \RuntimeException('BackBee application has to be initialized')
            );
        }

        if (null === $token = $application->getBBUserToken()) {
            throw new SecurityException('Enable to update : unauthorized user', SecurityException::UNAUTHORIZED_USER);
        }

        $em = $dispatcher->getApplication()->getEntityManager();
        if (null === $revision = $content->getDraft()) {
            if (null === $revision = $em->getRepository('BackBee\CoreDomain\ClassContent\Revision')->getDraft($content, $token)) {
                throw new ClassContentException('Enable to get draft', ClassContentException::REVISION_MISSING);
            }
            $content->setDraft($revision);
        }

        $content->releaseDraft();
        if (0 == $revision->getRevision() || $revision->getRevision() == $content->getRevision) {
            throw new ClassContentException('Content is up to date', ClassContentException::REVISION_UPTODATE);
        }

        $lastCommitted = $em->getRepository('BackBee\CoreDomain\ClassContent\Revision')->findBy([
            '_content'  => $content,
            '_revision' => $content->getRevision(),
            '_state'    => Revision::STATE_COMMITTED,
        ]);
        if (null === $lastCommitted) {
            throw new ClassContentException(
                'Enable to get last committed revision',
                ClassContentException::REVISION_MISSING
            );
        }

        $content->updateDraft($lastCommitted);
    }

    /**
     * Occurs on element.file.postremove events.
     * 
     * @param Event $event
     */
    public function onRemoveElementFile(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();

        try {
            $content = $event->getEventArgs()->getEntity();
            if (!($content instanceof ElementFile)) {
                return;
            }

            $includePath = array($application->getStorageDir(), $application->getMediaDir());
            if (null !== $application->getBBUserToken()) {
                $includePath[] = $application->getTemporaryDir();
            }

            $filename = $content->path;
            File::resolveFilepath($filename, null, array('include_path' => $includePath));

            @unlink($filename);
        } catch (\Exception $e) {
            $application->warning('Unable to delete file: '.$e->getMessage());
        }
    }

    /**
     * Occurs on rest.controller.classcontentcontroller.getAction.postcall events.
     * 
     * @param Event $event
     */
    public function onPostCall(Event $event)
    {
        $response = $event->getResponse();
        if ($response->headers->get('content-type') === 'text/html') {
            return;
        }

        $application = $event->getApplication();
        $renderer = $application->getRenderer();
        $content = json_decode($response->getContent());

        $result = false;
        if ($content instanceof \StdClass) {
            if (isset($content->type) && isset($content->parameters)) {
                if (isset($content->parameters->rendermode)) {
                    $result = true;
                }
            }
        }

        if (!$result) {
            return;
        }

        $rendermodeParam = $content->parameters->rendermode;
        $classname =  AbstractClassContent::getClassnameByContentType($content->type);

        $modes = ['default' => 'Default mode'];
        foreach ($renderer->getAvailableRenderMode(new $classname()) as $mode) {
            $modes[$mode] = ucfirst(str_replace('_', ' ', $mode));
        }

        $rendermodeParam->options = $modes;

        $response->setContent(json_encode($content));
    }

    /**
     * Occurs on event ``bbapplication.init`` to set AbstractContent::$ignoreUnknownClassname according to bbapp parameters.
     * @TODO gvf this should be moved from here
     * @param Event $event
     */
    public function onApplicationInit(Event $event)
    {
        // @TODO if app is in debug mode pass the mode, this might be done outside in extension probably
            AbstractClassContent::throwExceptionOnUnknownClassname($this->exceptionOnUnknownClassname);
    }
}
