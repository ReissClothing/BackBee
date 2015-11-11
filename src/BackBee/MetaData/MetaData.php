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

namespace BackBee\MetaData;

use BackBee\CoreDomain\ClassContent\AbstractClassContent;
use BackBee\CoreDomain\ClassContent\ContentSet;
use BackBee\CoreDomain\ClassContent\Element\File;
use BackBee\CoreDomain\ClassContent\Element\Keyword;
use BackBee\CoreDomain\ClassContent\Element\Text;
use BackBee\CoreDomain\NestedNode\Page;

/**
 * A metadata.
 *
 * Metadata instance is composed by a name and a set of key/value attributes
 * The attribute can be staticaly defined in yaml file or to be computed:
 *
 *     description:
 *       name: 'description'
 *       content:
 *         default: "Default value"
 *         layout:
 *           f5da92419743370d7581089605cdbc6e: $ContentSet[0]->$actu[0]->$chapo
 *       lang: 'en'
 *
 * In this example, the attribute `lang` is static and set to `fr`, the attribute
 * `content` will be set to `Default value`:
 *     <meta name="description" content="Default value" lang="en">
 *
 * But if the page has the layout `f5da92419743370d7581089605cdbc6e` the attribute
 * `content` will set according to the scheme:
 * value of the element `chapo` of the first `content `actu` in the first column.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaData implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * The name of the metadata.
     *
     * @var string
     */
    private $name;

    /**
     * An array of attributes.
     *
     * @var array
     */
    private $attributes;

    /**
     * The scheme to compute for dynamic attributes.
     *
     * @var array
     */
    private $scheme;

    /**
     * The attributes to be computed.
     *
     * @var array
     */
    private $is_computed;

    /**
     * Class constructor.
     *
     * @param string $name
     */
    public function __construct($name = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }

        $this->attributes = array();
        $this->scheme = array();
        $this->is_computed = array();
    }

    /**
     * Retuns the name of the metadata.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the metadata.
     *
     * @param string $name
     *
     * @return \BackBee\MetaData\MetaData
     *
     * @throws \BackBee\Exception\BBException Occurs if $name if not a valid string
     */
    public function setName($name)
    {
        if (false === preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name)) {
            throw new \BackBee\Exception\InvalidArgumentException('Invalid name for metadata: \'%s\'', $name);
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Checks if the provided attribute exists for this metadata.
     *
     * @param string $attribute The attribute looked for
     *
     * @return Boolean Returns TRUE if the attribute is defined for the metadata, FALSE otherwise
     * @codeCoverageIgnore
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->attributes);
    }

    /**
     * Returns the value of the attribute.
     *
     * @param string $attribute The attribute looked for
     * @param string $default   Optional, the default value return if attribute does not exist
     *
     * @return string
     */
    public function getAttribute($attribute, $default = '')
    {
        return (true === $this->hasAttribute($attribute)) ? $this->attributes[$attribute] : $default;
    }

    /**
     * Sets the value of the attribute.
     *
     * @param string                              $attribute
     * @param string                              $value
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content   Optional, if the attribute is computed
     *                                                        the content on which apply the scheme
     * @return \BackBee\MetaData\MetaData
     */
    public function setAttribute($attribute, $value, AbstractClassContent $content = null)
    {
        $originalValue = $value;
        $functions = explode('||', $value);
        $value = array_shift($functions);
        if (0 < preg_match('/(\$([a-z\/\\\\]+)(\[([0-9]+)\]){0,1}(->){0,1})+/i', $value)) {
            $this->scheme[$attribute] = $value;
            $this->is_computed[$attribute] = true;

            if (null !== $content && $originalValue === $value) {
                $this->computeAttributes($content);
            } else {
                $this->attributes[$attribute] = $originalValue;
            }
        } else {
            $this->attributes[$attribute] = $value;
            $this->is_computed[$attribute] = false;
        }

        return $this;
    }

    /**
     * Updates the scheme of the attribute.
     *
     * @param string                              $attribute
     * @param string                              $scheme
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content   Optional, if the attribute is computed
     *                                                        the content on which apply the scheme
     * @return \BackBee\MetaData\MetaData
     */
    public function updateAttributeScheme($attribute, $scheme, AbstractClassContent $content = null)
    {
        $functions = explode('||', $scheme);
        $value = array_shift($functions);
        if (0 < preg_match('/(\$([a-z\/\\\\]+)(\[([0-9]+)\]){0,1}(->){0,1})+/i', $value)) {
            $this->scheme[$attribute] = $scheme;
            if (null !== $content &&
                    true === isset($this->is_computed[$attribute]) &&
                    true === $this->is_computed[$attribute]) {
                $this->computeAttributes($content);
            }
        }

        return $this;
    }

    /**
     * Compute values of attributes according to the AbstractClassContent provided.
     *
     * @param  \BackBee\CoreDomain\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\MetaData\MetaData
     */
    public function computeAttributes(AbstractClassContent $content, Page $page = null)
    {
        foreach ($this->attributes as $attribute => $value) {
            if (true === $this->is_computed[$attribute] && true === array_key_exists($attribute, $this->scheme)) {
                try {
                    $functions = explode('||', $value);
                    $matches = array();
                    if (false !== preg_match_all('/(\$([a-z_\/\\\\]+)(\[([0-9]+)\]){0,1}(->){0,1})+/i', $this->scheme[$attribute], $matches, PREG_PATTERN_ORDER)) {
                        $this->attributes[$attribute] = $this->scheme[$attribute];
                        $initial_content = $content;
                        $count = count($matches[0]);
                        for ($i = 0; $i < $count; $i++) {
                            $content = $initial_content;
                            foreach (explode('->', $matches[0][$i]) as $scheme) {
                                $draft = null;
                                if (true === is_object($content)) {
                                    if (null !== $draft = $content->getDraft()) {
                                        $content->releaseDraft();
                                    }
                                }

                                $newcontent = $content;
                                $m = array();
                                if (preg_match('/\$([a-z\/\\\\]+)(\[([0-9]+)\]){0,1}/i', $scheme, $m)) {
                                    if (3 < count($m) && $content instanceof ContentSet && 'ContentSet' === $m[1]) {
                                        $newcontent = $content->item($m[3]);
                                    } elseif (3 < count($m) && $content instanceof ContentSet) {
                                        $index = intval($m[3]);
                                        $classname = 'BackBee\CoreDomain\ClassContent\\'.str_replace('/', NAMESPACE_SEPARATOR, $m[1]);
                                        foreach ($content as $subcontent) {
                                            if (get_class($subcontent) == $classname) {
                                                if (0 === $index) {
                                                    $newcontent = $subcontent;
                                                } else {
                                                    $index--;
                                                }
                                            }
                                        }
                                    } elseif (true === is_object($content) && 1 < count($m)) {
                                        $property = $m[1];
                                        try {
                                            $newcontent = $content->$property;
                                        } catch (\Exception $e) {
                                            $newcontent = new Text();
                                        }
                                    }
                                }

                                if (null !== $draft) {
                                    $content->setDraft($draft);
                                }

                                $content = $newcontent;
                            }

                            if ($content instanceof AbstractClassContent && $content->isElementContent()) {
                                if (null !== $draft = $content->getDraft()) {
                                    $content->releaseDraft();
                                }

                                if ($content instanceof File) {
                                    $new_value = $content->path;
                                } else {
                                    $new_value = trim(str_replace(array("\n", "\r"), '', strip_tags(''.$content)));
                                }

                                $this->attributes[$attribute] = str_replace($matches[0][$i], $new_value, $this->attributes[$attribute]);

                                if (null !== $draft) {
                                    $content->setDraft($draft);
                                }
                            } elseif (true === is_array($content)) {
                                $v = array();
                                foreach ($content as $c) {
                                    if ($c instanceof Keyword) {
                                    }
                                    $v[] = trim(str_replace(array("\n", "\r"), '', strip_tags(''.$c)));
                                }
                                $this->attributes[$attribute] = str_replace($matches[0][$i], implode(',', $v), $this->attributes[$attribute]);
                            } else {
                                $new_value = trim(str_replace(array("\n", "\r"), '', strip_tags($content)));
                                $this->attributes[$attribute] = str_replace($matches[0][$i], $new_value, $this->attributes[$attribute]);
                            }
                        }
                    }

                    array_shift($functions);
                    if (0 < count($functions)) {
                        foreach ($functions as $fct) {
                            $parts = explode(':', $fct);
                            $functionName = array_shift($parts);
                            array_unshift($parts, $this->attributes[$attribute]);
                            $this->attributes[$attribute];
                            $this->attributes[$attribute] = call_user_func_array($functionName, $parts);
                        }
                    }
                } catch (\Exception $e) {}
            } elseif (preg_match('/^\#([a-z]+)$/i', $value, $matches)) {
                switch (strtolower($matches[1])) {
                    case 'url':
                        if (null !== $page) {
                            $this->attributes[$attribute] = $page->getUrl();
                        }
                    default:
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * Returns the number of attributes.
     *
     * @return int
     */
    public function count()
    {
        return count($this->attributes);
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $attributes = array();
        foreach ($this->attributes as $attribute => $value) {
            $attr = array(
                'attr'  => $attribute,
                'value' => $value,
            );

            $attributes[] = $attr;
        }

        return $attributes;
    }
}
