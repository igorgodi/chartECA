<?php

use Symfony\Component\HttpFoundation\Request;

// Pour y accéder, il faut être 
//	--> uniquement sur le serveur de prod
if (php_sapi_name() == "cli"
    || !isset($_SERVER['HTTP_HOST']) 
    || (
 	   $_SERVER['HTTP_HOST'] != "php56-prod.in.ac-reims.fr" 
	&& $_SERVER['HTTP_HOST'] != "eca.ac-reims.fr" 
       )
   ) 
{
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

require __DIR__.'/../vendor/autoload.php';
if (PHP_VERSION_ID < 70000) {
    include_once __DIR__.'/../var/bootstrap.php.cache';
}

$kernel = new AppKernel('prod', false);
if (PHP_VERSION_ID < 70000) {
    $kernel->loadClassCache();
}
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
