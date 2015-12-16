<?php
/**
 * @author    Pete Ward <peter.ward@reiss.com>
 * @date      31/03/2015
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Testing environment.
 */

if (!in_array(@$_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '::1',
    '10.0.0.1'
))) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Require kernel.
require_once __DIR__.'/../app/AppKernel.php';

// Initialize kernel and run the application.
$kernel = new AppKernel('test', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
