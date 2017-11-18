<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read https://symfony.com/doc/current/setup.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

// Pour y accéder, il faut être 
//	--> soit sur un serveur de développement, 
//	--> soit sur une serveur de préprod
if (php_sapi_name() == "cli"
    || !isset($_SERVER['HTTP_HOST']) 
    || (
 	   $_SERVER['HTTP_HOST'] != "php56-dev.in.ac-reims.fr" 
	&& $_SERVER['HTTP_HOST'] != "pp-erine.ac-reims.fr" 
	&& $_SERVER['HTTP_HOST'] != "eca2.ac-reims.fr"
       )
   ) 
{
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

require __DIR__.'/../vendor/autoload.php';
Debug::enable();

$kernel = new AppKernel('dev', true);
if (PHP_VERSION_ID < 70000) {
    $kernel->loadClassCache();
}
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
