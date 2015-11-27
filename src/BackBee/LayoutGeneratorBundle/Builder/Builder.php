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

namespace BackBee\LayoutGeneratorBundle\Builder;

use BackBee\CoreDomain\Site\Layout;
use BackBee\CoreDomain\Site\Site;
use BackBee\LayoutGeneratorBundle\UidGenerator\GeneratorInterface;

/**
 * @author gonzalo.vilaseca <gonzalo.vilaseca@reiss.com>
 */
class Builder
{
    /**
     * System extention config file.
     *
     * @var string
     */
    const EXTENSION = 'yml';

    /**
     * The uid generator to use
     * @var GeneratorInterface
     */
    private $uidGenerator;

    private $layoutConfig;

    public function __construct(GeneratorInterface $uidGenerator, $layoutConfig)
    {
        $this->layoutConfig = $layoutConfig;
        $this->uidGenerator = $uidGenerator;
    }

    public function generateLayout($layoutName, Site $site = null, $extension = self::EXTENSION)
    {

        $data = $this->layoutConfig[$layoutName];
        $uid  = $this->uidGenerator->generateUid($layoutName, $data, $site);

//            @todo this should be done from a manager not directly instantiating an instance
        $layout = new Layout($uid);

//        @todo warning when setting data other values are set to null!
        if (array_key_exists('columns', $data)) {
            $layout->setData($this->computeColumns($data['columns']));
        } else {
            throw new \Exception(
                'Layout ' . $layout->getLabel() . ' definition needs columns');
        }

        $layout->setPicPath($layout->getUid() . '.png');

        if ($site !== null) {
            $layout->setSite($site);
        }

        if (array_key_exists('label', $data) && $data['label'] !== null) {
            $layout->setLabel($data['label']);
        } else {
            $layout->setLabel(basename($layoutName, '.' . self::EXTENSION));
        }

        if (array_key_exists('template', $data) && !empty($data['template'])) {
            $this->computeTemplate($layout, $data['template']);
        }else{
//            @todo improve
            $layout->setPath($layoutName. '.html.twig');
        }


        return $layout;
    }

    public function generateAll(Site $site = null)
    {
        $layouts = [];

        foreach ($this->layoutConfig as $name => $config) {
            $layouts[] = $this->generateLayout($name, $site);
        }

        return $layouts;
    }

    protected function computeTemplate(Layout $layout, $value)
    {
        if ($value !== null) {
            if (strlen(pathinfo($value, PATHINFO_EXTENSION)) !== 0) {
                $layout->setPath($value);
            } else {
                throw new \Exception(
                    'Invalid template name for ' . $layout->getLabel() . ' layout.'
                );
            }

        }
    }

    protected function computeColumns($columns)
    {
        $data         = [];
        $columnParser = new ColumnParser();

        foreach ($columns as $key => $column) {
            $column['title'] = $key;
            $data[]          = $columnParser->parse($column);
        }

        return json_encode(['templateLayouts' => $data]);
    }
}
