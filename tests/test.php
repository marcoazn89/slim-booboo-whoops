<?php

require '../vendor/autoload.php';

$app = new \Slim\App([
    'debug'         => true,
    'whoops.editor' => 'sublime'
]);

//$app->add(new \SlimBooboo\Middleware());

$app->add(new SlimBoobooWhoops\Middleware($app,null,function() { error_log("testing callable");}));

$app->get('/whoops/', function($req, $res, $arg) {
	throw new Exception("Error Processing Request", 1);
});

$app->get('/booboo/', function($req, $res, $arg) {
	throw new \Exception\BooBoo(
	(new MyBooBoos\DatabaseError('The message for the client'))->enableLogging('The message for the logs'),
	(new \HTTP\Response())->withStatus(404)->withLanguage(\HTTP\Response\Language::DUTCH));
});

$app->get('/error/', function($req, $res, $arg) {
	$a->B();
});

$app->run();