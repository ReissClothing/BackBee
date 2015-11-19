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

namespace BackBee\CoreDomainBundle\Security\Authorization\Voter;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Voter\AclVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\NestedNode\AbstractNestedNode;
use BackBee\CoreDomain\NestedNode\Page;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBAclVoter extends AclVoter
{
    /**
     * Returns the vote for the given parameters.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token      A TokenInterface instance
     * @param object|ObjectIdentityInterface                                       $object     The object to secure
     * @param array                                                                $attributes An array of attributes associated with the method being invoked
     *
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (null === $object) {
            return self::ACCESS_ABSTAIN;
        }

        if ($object instanceof Page) {
            return $this->voteForPage($token, $object, $attributes);
        } elseif ($object instanceof AbstractNestedNode) {
            return $this->voteForNestedNode($token, $object, $attributes);
        } elseif ($object instanceof AbstractClassContent) {
            return $this->voteForClassContent($token, $object, $attributes);
//        } elseif ($object instanceof AbstractBundle) { @todo gvf what should we do with this?
//            return parent::vote($token, $object, $attributes);
        }

        return $this->voteForObject($token, $object, $attributes);
    }

    /**
     * Returns the vote for the cuurent object, if denied try the vote for the general object.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param object|ObjectIdentityInterface                                       $object
     * @param array                                                                $attributes
     *
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function voteForObject(TokenInterface $token, $object, array $attributes)
    {
        if (self::ACCESS_GRANTED !== $result = parent::vote($token, $object, $attributes)) {
            // try class-scope ace
            $objectIdentity = $this->getClassScopeObjectIdentity($object);
            $result = parent::vote($token, $objectIdentity, $attributes);
        }

        return $result;
    }

    /**
     * Returns the class-scope object identity for $object
     * @param ObjectIdentityInterface $object
     * @return ObjectIdentity
     */
    private function getClassScopeObjectIdentity($object)
    {
        $classname = ClassUtils::getRealClass($object);
        if ($object instanceof ObjectIdentityInterface) {
            $classname = $object->getType();
        }

        return new ObjectIdentity('all', $classname);
    }

    /**
     * Returns the vote for page object, recursively till root.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface  $token
     * @param  \BackBee\CoreDomain\NestedNode\Page                                             $page
     * @param  array                                                                $attributes
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function voteForPage(TokenInterface $token, Page $page, array $attributes)
    {
        if (self::ACCESS_DENIED === $result = $this->voteForObject($token, $page, $attributes)) {
            if (null !== $page->getParent()) {
                $result = $this->voteForPage($token, $page->getParent(), $attributes);
            }
        }

        return $result;
    }

    /**
     * Returns the vote for nested node object, recursively till root.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param  \BackBee\CoreDomain\NestedNode\AbstractNestedNode $node
     * @param  array                                                                $attributes
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function voteForNestedNode(TokenInterface $token, AbstractNestedNode $node, array $attributes)
    {
        if (self::ACCESS_DENIED === $result = $this->voteForObject($token, $node, $attributes)) {
            if (null !== $node->getParent()) {
                $result = $this->voteForNestedNode($token, $node->getParent(), $attributes);
            }
        }

        return $result;
    }

    /**
     * Returns the vote for class content object, recursively till AbstractClassContent.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     * @param  array                                                                $attributes
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function voteForClassContent(TokenInterface $token, AbstractClassContent $content, array $attributes)
    {
        if (null === $content->getProperty('category')) {
            return self::ACCESS_GRANTED;
        }

        if (self::ACCESS_DENIED === $result = $this->voteForObject($token, $content, $attributes)) {
            if (false !== $parent_class = get_parent_class($content)) {
                if ('BackBee\CoreDomain\ClassContent\AbstractClassContent' !== $parent_class) {
                    $parent_class = NAMESPACE_SEPARATOR.$parent_class;
                    $result = $this->voteForClassContent($token, new $parent_class('*'), $attributes);
                } else {
                    $objectIdentity = new ObjectIdentity('all', 'BackBee\CoreDomain\ClassContent\AbstractClassContent');
                    $result = parent::vote($token, $objectIdentity, $attributes);
                }
            }
        }

        return $result;
    }
}
