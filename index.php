<?php
use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container = new Container();
include __DIR__. '/helpers/setupContainerFromDotEnv.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response)  {
    $response->getBody()->write("Welcome to HTML storage Slim app");
    return $response;
});

include __DIR__ . '/routes/storageRoutes.php';

$app->run();