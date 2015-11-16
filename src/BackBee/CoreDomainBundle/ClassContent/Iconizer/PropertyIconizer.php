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

namespace BackBee\CoreDomainBundle\ClassContent\Iconizer;

use BackBee\CoreDomain\ClassContent\AbstractContent;
use BackBee\Routing\RouteCollection;

/**
 * Iconizer returning URI define by the class content property `iconized-by`.
 * 
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class PropertyIconizer implements IconizerInterface
{
    /**
     * Returns the URI of the icon of the provided content.
     * 
     * @param  AbstractContent $content The content.
     * 
     * @return string|null              The icon URL if found, null otherwise.
     */
    public function getIcon(AbstractContent $content)
    {
        if (null === $property = $content->getProperty('iconized-by')) {
            return null;
        }

        return $this->parseProperty($content, $property);
    }

    /**
     * Parses the content property and return the icon URL if found.
     * 
     * @param  AbstractContent $content  The content.
     * @param  string          $property The property to be parsed.
     * 
     * @return string|null               The icon URL if found, null otherwise.
     */
    private function parseProperty(AbstractContent $content, $property)
    {
        $currentContent = $content;
        foreach (explode('->', $property) as $part) {
            if ('@' === substr($part, 0, 1)) {
                return $this->iconizeByParam($currentContent, substr($part, 1));
            } elseif ($currentContent->hasElement($part)) {
                $currentContent = $this->iconizedByElement($currentContent, $part);
            }
            
            if ($currentContent instanceof AbstractContent) {
                continue;
            } else {
                return $currentContent;
            }

            return null;
        }
    }

    /**
     * Returns the icon URL from the parameter value.
     * 
     * @param  AbstractContent $content   The content.
     * @param  string          $paramName The parameter name.
     * 
     * @return string|null                The icon URL.
     */
    private function iconizeByParam(AbstractContent $content, $paramName)
    {
        if (null === $parameter = $content->getParam($paramName)) {
            return null;
        }

        if (empty($parameter['value'])) {
            return null;
        }

        return $parameter['value'];
    }

    /**
     * Returns the icon URL from the element value if $elementName is scalar, the subcontent otherwise.
     * 
     * @param  AbstractContent $content     The content.
     * @param  string          $elementName The element name.
     * 
     * @return AbstractContent|string       If $content->$elementName is a content, the subcontent, otherwise the icon URL.
     */
    private function iconizedByElement(AbstractContent $content, $elementName)
    {
        if ($content->$elementName instanceof AbstractContent) {
            return $content->$elementName;
        }

        if (empty($content->$elementName)) {
            return null;
        }

        return $content->$elementName;
    }

}
