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

namespace BackBee\LayoutGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create Layout.
 *
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class CreateLayoutCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bb:layout:create')
            ->addOption(
                'layout',
                null,
                InputOption::VALUE_OPTIONAL,
                'layout name'
            )
            ->addOption(
                'site',
                null,
                InputOption::VALUE_OPTIONAL,
                'site label or URI'
            )
            ->setDescription('Create backbee layout')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command create layout based on layout definition and for a given site

<info>php %command.full_name% --layout=file-name-definition --site=label|uri</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $layoutName  = $input->getOption('layout');
        $site    = $input->getOption('site');
        $builder = $this->getContainer()->get('bbapp.layout_generator.builder');

        if ($layoutName === null) {
            $builder->generateAll($site);
            $output->writeln('Layouts created.');
        } else {
            if ($this->layoutExists($layoutName, $site)) {
                throw new \Exception('layout ' . $layout . ' already exists.');
            }

            $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
            if ($site === null) {
                $sites = $em->getRepository('BackBee\CoreDomain\Site\Site')->findall();
                foreach ($sites as $site) {
                    $layout = $builder->generateLayout($layoutName, $site);
                    $em->persist($layout);
                    $em->flush();
                }
            } else {
                $layout = $builder->generateLayout($layoutName, $site);
                $em->persist($layout);
                $em->flush();
            }
            $output->writeln('Layout created.');
        }
    }

    private function layoutExists($layoutName, $site)
    {
//        @todo
        return false;
    }
}
