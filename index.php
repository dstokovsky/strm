<?php

ini_set('display_errors', 0);

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;

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

$modules = scandir(__DIR__ . '/modules');
foreach ($modules as $module)
{
    if(!is_dir($module))
    {
        require_once __DIR__ . '/modules/' . $module;
    }
}

$app->handle();