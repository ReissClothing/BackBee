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

namespace BackBee\ApiBundle\Patcher;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface PatcherInterface
{
    const EXCEPTION_ON_INVALID_OPERATION = 1;
    const FALSE_ON_INVALID_OPERATION = 2;

    /**
     * list of available operations.
     */
    const TEST_OPERATION = 'test';
    const REMOVE_OPERATION = 'remove';
    const ADD_OPERATION = 'add';
    const REPLACE_OPERATION = 'replace';
    const MOVE_OPERATION = 'move';
    const COPY_OPEARATION = 'copy';

    /**
     * [setRightManager description].
     *
     * @param RightManager $right_manager [description]
     *
     * @return BackBee\ApiBundle\Patcher\PatcherInterface
     */
    public function setRightManager(RightManager $right_manager);

    /**
     * [getRightManager description].
     *
     * @return [type] [description]
     */
    public function getRightManager();

    /**
     * [patch description].
     *
     * @param object  $entity
     * @param array   $operations
     * @param integer $on_invalid_operation
     *
     * @return [type]
     */
    public function patch($entity, array $operations, $on_invalid_operation = self::EXCEPTION_ON_INVALID_OPERATION);
}
