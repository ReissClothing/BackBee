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

namespace BackBee\CoreDomainBundle\Event\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use BackBee\CoreDomainBundle\Event\Event;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\CoreDomain\NestedNode\Section;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Page events listener.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PageListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param EntityManagerInterface        $entityManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(EntityManagerInterface $entityManager, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
    }
    /**
     * @param \BackBee\Event\Event $event
     */
    public function onPostLoad(Event $event)
    {
        $page = $event->getTarget();

        if (!($page instanceof Page)) {
            return;
        }

        $isBbSessionActive = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') === false;
        $page->setUseUrlRedirect($isBbSessionActive);
    }

    public function setSectionHasChildren(Section $section = null, $pageCountModifier = 0)
    {
        if ($section !== null) {
            $repo = $this->entityManager->getRepository('BackBee\CoreDomain\NestedNode\Page');
            $notDeletedDescendants = $repo->getNotDeletedDescendants($section->getPage(), 1, false, [], true, 0, 2);

            $section->setHasChildren(($notDeletedDescendants->getIterator()->count() + $pageCountModifier) > 0);
            $this->entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata('BackBee\CoreDomain\NestedNode\Section'), $section);
        }
    }

    /**
     * Occur on nestednode.page.preupdate events and nestednode.section.preupdate.
     *
     * @access public
     *
     * @param Event $event
     */
    public function onPreUpdate(Event $event)
    {
        $page = $event->getTarget();
        $eventArgs = $event->getEventArgs();
        $updateParents = false;
        $new = $old = null;

        if ($eventArgs instanceof PreUpdateEventArgs) {
            if ($page instanceof Page && $eventArgs->hasChangedField('_section')) {
                $old = $eventArgs->getOldValue('_section');
                $new = $eventArgs->getNewValue('_section');

                if ($new->getUid() === $page->getUid()) {
                    return;
                }
                $updateParents = true;
            }

            if ($page instanceof Page && $eventArgs->hasChangedField('_state')) {
                if ($page->getParent() !== null) {
                    if ($eventArgs->getNewValue('_state') >= 4) {
                        $old = $page->getParent()->getSection();
                    } else {
                        $new = $page->getParent()->getSection();
                    }
                    $updateParents = true;
                }
            }

            if ($page instanceof Section && $eventArgs->hasChangedField('_parent')) {
                $old = $eventArgs->getOldValue('_parent');
                $new = $eventArgs->getNewValue('_parent');
                $updateParents = true;
            }

            if ($updateParents) {
                self::setSectionHasChildren($old, -1);
                self::setSectionHasChildren($new, +1);
            }
        }
    }

    /**
     * Occur on nestednode.page.preupdate events and nestednode.section.preupdate.
     *
     * @access public
     *
     * @param Event $event
     */
    public function onFlushPage(Event $event)
    {
        $uow = $this->entityManager->getUnitOfWork();
        $page = $event->getTarget();
        if ($uow->isScheduledForInsert($page) && $page->getParent() !== null && $page->getState() < 4) {
            self::setSectionHasChildren($page->getParent()->getSection(), +1);
        }
    }
}
