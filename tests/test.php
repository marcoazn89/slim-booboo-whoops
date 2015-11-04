<?php

require '../vendor/autoload.php';

$app = new \Slim\App([
    'debug'         => true,
    'whoops.editor' => 'sublime'
]);

//$app->add(new \SlimBooboo\Middleware());
$logger = (new \Monolog\Logger('TEST'))
  ->pushHandler(
    new \Monolog\Handler\FingersCrossedHandler(
      new \Monolog\Handler\StreamHandler(__DIR__.'/log'),
      \Monolog\Logger::WARNING
    )
  );

$app->add(new SlimBoobooWhoops\Middleware($app,null, $logger));

$app->get('/whoops/', function($req, $res, $arg) {
	throw new Exception("Error Processing Request", 1);
});

$app->get('/booboo/', function($req, $res, $arg) {
	throw new \Exception\BooBoo(
	new MyBooBoos\DatabaseError('The message for the client', 'The message for the logs', [1,2,3,3,54]),
	(new \HTTP\Response())->withStatus(404)->withLanguage(\HTTP\Response\Language::DUTCH));
});

$app->get('/error/', function($req, $res, $arg) {
	$a->B();
});

$app->run();