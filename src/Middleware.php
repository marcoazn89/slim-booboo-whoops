<?php
namespace SlimBoobooWhoops;

use Exception\BooBoo;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;

class Middleware {

	protected $app;
	protected $lastAction;

	public function __construct($app, array $defaultPaths = null, $lastAction) {
		$this->app = $app;

		if( ! is_null($defaultPaths)) {
			foreach($defaultPaths as $format => $path) {
				BooBoo::defaultErrorPath($format, $path);
			}
		}

		$this->lastAction = $lastAction;
	}

	public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, $next) {
		$container = $this->app->getContainer();

		$settings  = $container['settings'];

    // Enable PrettyPageHandler with editor options
    $prettyPageHandler = new PrettyPageHandler();

    // Enable JsonResponseHandler when request is AJAX
    $jsonResponseHandler = new JsonResponseHandler();
    $jsonResponseHandler->onlyForAjaxRequests(true);

    // Add more information to the PrettyPageHandler
    $prettyPageHandler->addDataTable('Slim Application', [
     'Application Class' => get_class($this->app),
     'Script Name'       => $this->app->environment->get('SCRIPT_NAME'),
     'Request URI'       => $this->app->environment->get('PATH_INFO') ?: '<none>',
    ]);

    $prettyPageHandler->addDataTable('Slim Application (Request)', array(
      'Accept Charset'  => $this->app->request->getHeader('ACCEPT_CHARSET') ?: '<none>',
      'Content Charset' => $this->app->request->getContentCharset() ?: '<none>',
      'Path'            => $this->app->request->getUri()->getPath(),
      'Query String'    => $this->app->request->getUri()->getQuery() ?: '<none>',
      'HTTP Method'     => $this->app->request->getMethod(),
      'Base URL'        => (string) $this->app->request->getUri(),
      'Scheme'          => $this->app->request->getUri()->getScheme(),
      'Port'            => $this->app->request->getUri()->getPort(),
      'Host'            => $this->app->request->getUri()->getHost(),
    ));

    // Set Whoops to default exception handler
    $whoops = new \Whoops\Run;
    $whoops->pushHandler($prettyPageHandler);
    $whoops->pushHandler($jsonResponseHandler);
		$whoops->register();

		// Overwrite the errorHandler
		$container['errorHandler'] = function($c) use ($whoops) {
			return function($request, $response, $exception) use ($whoops) {

				if($exception instanceof BooBoo) {
					// Store the BooBoo error body response in a buffer
					ob_start();
					BooBoo::exceptionHandler($exception);
					$buffer = ob_get_contents();
					ob_end_clean();

					// By creating a new response object, all the headers set by BooBoo get resynced
					$response = new \HTTP\Response();

					return $response->overwrite($buffer);
				}
				else {
					$handler = \Whoops\Run::EXCEPTION_HANDLER;

				  ob_start();

				  $whoops->$handler($exception);

				  $content = ob_get_clean();
				  $code = $exception instanceof HttpException ? $exception->getStatusCode() : 500;

				  return $response
				         ->withStatus($code)
				         ->withHeader('Content-type', 'text/html')
				         ->write($content);
				}
			};
		};

		return $next($request, $response);
	}
}