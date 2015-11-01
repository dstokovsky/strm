<?php

ini_set('display_errors', 0);

require_once __DIR__ . '/vendor/autoload.php';

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use Storage\SessionStorage;
use Storage\AccessTokenStorage;
use Storage\ClientStorage;
use Storage\ScopeStorage;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;

try
{
    // Use Loader() to autoload our model
    $loader = new Loader();
    $loader->registerDirs(
        array(
            __DIR__ . '/models/'
        )
    )->register();

    $di = new FactoryDefault();
    // Настройка сервиса базы данных
    $di->set('db', function () {
        return new PdoMysql(
            array(
                "host"     => 'localhost',
                "username" => 'streaming',
                "password" => '$tr3@m1nG',
                "dbname"   => 'streaming',
            )
        );
    });
    
    $app = new Micro($di);

    $server = new AuthorizationServer;
    $server->setSessionStorage(new SessionStorage);
    die('LLLLL');
    $server->setAccessTokenStorage(new AccessTokenStorage);
    $server->setClientStorage(new ClientStorage);
    $server->setScopeStorage(new ScopeStorage);
    $clientCredentials = new ClientCredentialsGrant();
    $server->addGrantType($clientCredentials);

    $app->get('/api/access/{client_key:[0-9A-Za-z_\-]+}/{client_secret:[0-9A-Za-z_\-]+}', function ($key, $secret) use ($server) {
        $response = new Response();
        $token = $server->issueAccessToken();

        $response->setJsonContent($token);
        return $response;
    });

    $modules = scandir(__DIR__ . '/modules');
    foreach ($modules as $module)
    {
        if(!is_dir($module))
        {
            require_once __DIR__ . '/modules/' . $module;
        }
    }

    $app->handle();
}
catch (\Exception $e)
{
    print_r($e);
}