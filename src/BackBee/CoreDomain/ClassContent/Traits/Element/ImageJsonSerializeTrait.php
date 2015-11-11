<?php

namespace BackBee\CoreDomain\ClassContent\Traits\Element;

use BackBee\CoreDomain\ClassContent\AbstractContent;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
trait ImageJsonSerializeTrait
{
    /**
     * @see AbstractContent::jsonSerialize
     */
    public function jsonSerialize($format = AbstractContent::JSON_DEFAULT_FORMAT)
    {
        $data = parent::jsonSerialize($format);

        if (AbstractContent::JSON_DEFAULT_FORMAT === $format || AbstractContent::JSON_CONCISE_FORMAT === $format) {
            $data['extra']['image_width'] = $this->getParamValue('width');
            $data['extra']['image_height'] = $this->getParamValue('height');
        }

        return $data;
    }
}
