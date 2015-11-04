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

namespace BackBee\CoreDomainBundle\Rewriting;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\ClassContent\AbstractContent;
use BackBee\CoreDomain\NestedNode\Page;
use BackBee\Rewriting\Exception\RewritingException;
use BackBee\Utils\StringUtils;

/**
 * Utility class to generate page URL according config rules.
 *
 * Available options are:
 *    * preserve-online  : if true, forbid the URL updating for online page
 *    * preserve-unicity : if true check for unique computed URL
 *
 * Available rules are:
 *    * _root_      : scheme for root node
 *    * _default_   : default scheme
 *    * _content_   : array of schemes indexed by content classname
 *
 * Available params are:
 *    * $parent     : page parent url
 *    * $uid        : page uid
 *    * $title      : the urlized form of the title
 *    * $date       : the creation date formated to YYYYMMDD
 *    * $datetime   : the creation date formated to YYYYMMDDHHII
 *    * $time       : the creation date formated to HHIISS
 *    * $content->x : the urlized form of the 'x' property of content
 *    * $ancestor[x]: the ancestor of level x url
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * Current BackBee application.
     *
     * @var BackBee\BBApplication
     */
    private $application;

    /**
     * if true, forbid the URL updating for online page.
     *
     * @var boolean
     */
    private $preserveOnline = true;

    /**
     * if true, check for unique computed URL.
     *
     * @var boolean
     */
    private $preserveUnicity = true;

    /**
     * Available rewriting schemes.
     *
     * @var array
     */
    private $schemes = [];

    /**
     * Array of class content used by one of the schemes.
     *
     * @var array
     */
    private $descriminators;

    /**
     * Class constructor.
     *
     * @param \BackBee\BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->application = $application;

        if (null !== $rewritingConfig = $this->application->getConfig()->getRewritingConfig()) {
            if (array_key_exists('preserve-online', $rewritingConfig)) {
                $this->setPreserveOnline(true === $rewritingConfig['preserve-online']);
            }

            if (array_key_exists('preserve-unicity', $rewritingConfig)) {
                $this->setPreserveUnicity(true === $rewritingConfig['preserve-unicity']);
            }

            if (isset($rewritingConfig['scheme']) && is_array($rewritingConfig['scheme'])) {
                $this->schemes = $rewritingConfig['scheme'];
            }
        }
    }

    /**
     * Returns true if UrlGenerator is configured to preserve url for pages with online state, else false.
     *
     * @return boolean
     */
    public function isPreserveOnline()
    {
        return $this->preserveOnline;
    }

    /**
     * Setter for UrlGenerator's preserve online option.
     *
     * @param boolean $preserveOnline
     */
    public function setPreserveOnline($preserveOnline)
    {
        $this->preserveOnline = (boolean) $preserveOnline;
    }

    /**
     * Returns true if UrlGenerator is configured to preserve url for pages with online state, else false.
     *
     * @return boolean
     */
    public function isPreserveUnicity()
    {
        return $this->preserveUnicity;
    }

    /**
     * Setter for UrlGenerator's preserve online option.
     *
     * @param boolean $preserveUnicity
     */
    public function setPreserveUnicity($preserveUnicity)
    {
        $this->preserveUnicity = (boolean) $preserveUnicity;
    }

    /**
     * Returns the list of class content names used by one of schemes
     * Dynamically add a listener on descrimator.onflush event to RewritingListener.
     *
     * @return array
     */
    public function getDiscriminators()
    {
        if (null === $this->descriminators) {
            $this->descriminators = [];

            if (array_key_exists('_content_', $this->schemes)) {
                foreach (array_keys($this->schemes['_content_']) as $descriminator) {
                    $this->descriminators[] = 'BackBee\ClassContent\\'.$descriminator;

                    if (null !== $this->application->getEventDispatcher()) {
                        $this
                            ->application
                            ->getEventDispatcher()
                            ->addListener(
                                str_replace(NAMESPACE_SEPARATOR, '.', $descriminator).'.onflush',
                                ['BackBee\Event\Listener\RewritingListener', 'onFlushContent']
                            )
                        ;
                    }
                }
            }
        }

        return $this->descriminators;
    }

    /**
     * Generates and returns url for the provided page.
     *
     * @param Page                 $page    The page to generate its url
     * @param AbstractClassContent $content The optional main content of the page
     * @return string
     */
    public function generate(Page $page, AbstractClassContent $content = null, $force = false, $exceptionOnMissingScheme = true)
    {
        if (!is_bool($force)) {
            throw new \InvalidArgumentException(sprintf(
                '%s method expect `force parameter` to be type of boolean, %s given',
                __METHOD__,
                gettype($force)
            ));
        }

        if (
            null !== $page->getUrl(false)
            && $page->getState() & Page::STATE_ONLINE
            && (!$force && $this->preserveOnline)
        ) {
            return $page->getUrl(false);
        }

        if ($page->isRoot() && array_key_exists('_root_', $this->schemes)) {
            return $this->doGenerate($this->schemes['_root_'], $page, $content);
        }

        if (isset($this->schemes['_layout_']) && is_array($this->schemes['_layout_'])) {
            if (array_key_exists($page->getLayout()->getUid(), $this->schemes['_layout_'])) {
                return $this->doGenerate($this->schemes['_layout_'][$page->getLayout()->getUid()], $page);
            }
        }

        if (null !== $content && array_key_exists('_content_', $this->schemes)) {
            $shortClassname = str_replace(AbstractContent::CLASSCONTENT_BASE_NAMESPACE, '', get_class($content));
            if (array_key_exists($shortClassname, $this->schemes['_content_'])) {
                return $this->doGenerate($this->schemes['_content_'][$shortClassname], $page, $content);
            }
        }

        if (array_key_exists('_default_', $this->schemes)) {
            return $this->doGenerate($this->schemes['_default_'], $page, $content);
        }

        $url = $page->getUrl(false);
        if (!empty($url)) {
            return $url;
        }

        if (true === $exceptionOnMissingScheme) {
            throw new RewritingException(
                sprintf('No rewriting scheme found for Page (#%s)', $page->getUid()),
                RewritingException::MISSING_SCHEME
            );
        }

        return '/'.$page->getUid();
    }

    /**
     * Checks for the uniqueness of the URL and postfixe it if need.
     *
     * @param \BackBee\CoreDomain\NestedNode\Page $page The page
     * @param string                   &$url The reference of the generated URL
     */
    public function getUniqueness(Page $page, $url)
    {
        if (!$this->preserveUnicity) {
            return $url;
        }

        $pageRepository = $this->application->getEntityManager()->getRepository('BackBee\CoreDomain\NestedNode\Page');
        if (null === $pageRepository->findOneBy(['_url' => $url, '_root' => $page->getRoot(), '_state' => $page->getUndeletedStates()])) {
            return $url;
        }

        $baseUrl = $url.'-%d';

        $matches = [];
        $existings = [];
        if (preg_match('#(.*)\/$#', $baseUrl, $matches)) {
            $baseUrl = $matches[1].'-%d/';
            $existings = $pageRepository->createQueryBuilder('p')
                    ->andRootIs($page->getRoot())
                    ->andWhere('p._url LIKE :url')
                    ->setParameter('url', $matches[1] . '%/')
                    ->getQuery()
                    ->getResult()
            ;
        } else {
            $existings = $this->application->getEntityManager()->getConnection()->executeQuery(
                'SELECT p.uid FROM page p LEFT JOIN section s ON s.uid = p.section_uid WHERE s.root_uid = :root AND p.url REGEXP :regex',
                [
                    'regex' => str_replace(['+'], ['[+]'], $url).'(-[0-9]+)?$',
                    'root'  => $page->getRoot()->getUid(),
                ]
            )->fetchAll();

            $uids = [];

            foreach ($existings as $existing) {
                $uids[] = $existing['uid'];
            }

            $existings = $pageRepository->findBy(['_uid' => $uids]);
        }

        $existingUrls = [];
        foreach ($existings as $existing) {
            if (!$existing->isDeleted() && $existing->getUid() !== $page->getUid()) {
                $existingUrls[] = $existing->getUrl(false);
            }
        }

        $count = 1;
        while (in_array($url, $existingUrls)) {
            $url = sprintf($baseUrl, $count++);
        }

        return $url;
    }

    /**
     * Computes the URL of a page according to a scheme.
     *
     * @param array         $scheme  The scheme to apply
     * @param Page          $page    The page
     * @param  AbstractClassContent $content The optionnal main content of the page
     * @return string        The generated URL
     */
    private function doGenerate($scheme, Page $page, AbstractClassContent $content = null)
    {
        $replacement = [
            '$parent'   => $page->isRoot() ? '' : $page->getParent()->getUrl(false),
            '$title'    => StringUtils::urlize($page->getTitle()),
            '$datetime' => $page->getCreated()->format('ymdHis'),
            '$date'     => $page->getCreated()->format('ymd'),
            '$time'     => $page->getCreated()->format('His'),
        ];

        $matches = [];
        if (preg_match_all('/(\$content->[a-z]+)/i', $scheme, $matches)) {
            foreach ($matches[1] as $pattern) {
                $property = explode('->', $pattern);
                $property = array_pop($property);

                try {
                    $replacement[$pattern] = StringUtils::urlize($content->$property);
                } catch (\Exception $e) {
                    $replacement[$pattern] = '';
                }
            }
        }

        $matches = [];
        if (preg_match_all('/(\$ancestor\[([0-9]+)\])/i', $scheme, $matches)) {
            foreach ($matches[2] as $level) {
                $ancestor = $this->application
                    ->getEntityManager()
                    ->getRepository('BackBee\CoreDomain\NestedNode\Page')
                    ->getAncestor($page, $level)
                ;
                if (null !== $ancestor && $page->getLevel() > $level) {
                    $replacement['$ancestor['.$level.']'] = $ancestor->getUrl(false);
                } else {
                    $replacement['$ancestor['.$level.']'] = '';
                }
            }
        }

        $url = preg_replace('/\/+/', '/', str_replace(array_keys($replacement), array_values($replacement), $scheme));

        return $this->getUniqueness($page, $url);
    }
}
