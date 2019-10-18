<?php
error_reporting(!E_ALL);
//ini_set('max_execution_time', 600);
//echo phpinfo();die;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../cfg/cfg.php';
require_once __DIR__ . '/lib/common.php';
require_once __DIR__ . '/lib/util.php';
require_once __DIR__ . '/middleWare/JWTAuth.php';

$configuration = [
  'settings' => [
    'displayErrorDetails' => true,
    //'displayErrorDetails' => false
  ],
];
$c                      = new \Slim\Container($configuration);
$app                    = new \Slim\App($c);
$container              = $app->getContainer();
$container['uploadDir'] = __DIR__ . '/../upload';
//$app = new Slim\App();
$app->add(function ($req, $res, $next) {
  $response = $next($req, $res);
  return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->add(new \Slim\Middleware\JWTAuth([
  'secret' => JWT_secret,
  'dbName' => dbName,
  'dbUsr'  => dbUsr,
  'dbPwd'  => dbPwd,
]));

require_once __DIR__ . '/routes/user.php';
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/upload.php';
$app->GET('/ping', function ($req, $res, $args) {
  return $res->write('pong');
});
$app->run();
