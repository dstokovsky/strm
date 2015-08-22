<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

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

$app->get('/api/users/{id:[0-9]+}', function ($id) use ($app) {
    $phql = "SELECT * FROM User WHERE id = :id:";
    $user = $app->modelsManager->executeQuery($phql, ['id' => $id])->getFirst();

    // Create a response
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    if (!empty($user))
    {
        $content = [
            'code' => 200,
            'status' => 'Ok',
            'data'   => [
                'id'   => $user->id,
                'name' => $user->email,
                'first_name' => html_entity_decode($user->first_name, ENT_QUOTES, 'UTF-8'),
                'second_name' => html_entity_decode($user->second_name, ENT_QUOTES, 'UTF-8'),
                'created_at' => $user->created_at,
            ]
        ];
    }
    $response->setJsonContent($content);

    return $response;
});

$app->post('/api/users', function () use ($app) {
    $user = $app->request->getJsonRawBody();
 
    $phql = 'INSERT INTO User (email, first_name, second_name, created_at, updated_at) ' . 
        'VALUES (:email:, :first_name:, :second_name:, :created_at:, :updated_at:)';

    $status = $app->modelsManager->executeQuery($phql, array(
        'email' => $user->email,
        'first_name' => htmlentities(strip_tags(trim($user->first_name)), ENT_QUOTES, 'UTF-8'),
        'second_name' => htmlentities(strip_tags(trim($user->second_name)), ENT_QUOTES, 'UTF-8'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ));
    
    $response = new Response();
    $content = [];
    if ($status->success())
    {
        $user->id = $status->getModel()->id;
        $content = [
            'code' => 201,
            'status' => 'Created',
            'data' => $user,
        ];
    }
    else
    {
        $errors = array();
        foreach ($status->getMessages() as $message)
        {
            $errors[] = $message->getMessage();
        }
        $content = [
            'code' => 409,
            'status' => 'Conflict',
            'data' => $errors,
        ];
    }
    $response->setJsonContent($content);

    return $response;
});

$app->put('/api/users/{id:[0-9]+}', function ($id) use ($app) {
    $user = $app->request->getJsonRawBody();

    $phql = "UPDATE User SET %s WHERE id = :id:";
    $set = $binds = [];
    $binds['id'] = (int) $id;
    if(isset($user->email))
    {
        $set[] = 'email=:email:';
        $binds['email'] = $user->email;
    }
    
    if(isset($user->first_name))
    {
        $set[] = 'first_name=:first_name:';
        $binds['first_name'] = htmlentities(strip_tags(trim($user->first_name)), ENT_QUOTES, 'UTF-8');
    }
    
    if(isset($user->second_name))
    {
        $set[] = 'second_name=:second_name:';
        $binds['second_name'] = htmlentities(strip_tags(trim($user->second_name)), ENT_QUOTES, 'UTF-8');
    }
    
    $set[] = 'updated_at=:updated_at:';
    $binds['updated_at'] = date('Y-m-d H:i:s');
    $status = $app->modelsManager->executeQuery(sprintf($phql, implode(', ', $set)), $binds);

    $response = new Response();
    $content = ['code' => 200, 'status' => 'Ok', 'data' => $status->getModel()];
    if (!$status->success())
    {
        $errors = array();
        foreach ($status->getMessages() as $message)
        {
            $errors[] = $message->getMessage();
        }

        $content = [
            'code' => 409,
            'status'   => 'Conflict',
            'data' => $errors,
        ];
    }
    $response->setJsonContent($content);

    return $response;
});

$app->delete('/api/users/{id:[0-9]+}', function ($id) use ($app) {
    $phql = "DELETE FROM User WHERE id = :id:";
    $status = $app->modelsManager->executeQuery($phql, ['id' => $id]);

    $response = new Response();
    $content = ['code' => 200, 'status' => 'Ok', 'data' => []];
    if (!$status->success())
    {
        $errors = array();
        foreach ($status->getMessages() as $message)
        {
            $errors[] = $message->getMessage();
        }

        $content = [
            'code' => 409,
            'status' => 'Conflict',
            'data' => $errors,
        ];
    }
    $response->setJsonContent($content);

    return $response;
});

$app->post('/api/messages/{author_id:[0-9]+}/{recipient_id:[0-9]+}', function ($author_id, $recipient_id) use ($app) {
    $message = $app->request->getJsonRawBody();
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    if(!isset($message->text) || empty($message->text))
    {
        $content = ['code' => 500, 'status' => 'Internal Error', 'data' => 
            ['Invalid message text, it is not set or empty or longer than expected.']];
    }
    
    $author = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $author_id]]);
    if(empty($author))
    {
        $content['data'][] = 'There is no user with such author id.';
    }
    
    $recipient = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $recipient_id]]);
    if(empty($recipient))
    {
        $content['data'][] = 'There is no user with such recipient id.';
    }
    
    if(empty($content['data']))
    {
        $phql = 'INSERT INTO Message (author_id, recipient_id, text, created_at, updated_at) ' . 
            'VALUES (:author_id:, :recipient_id:, :text:, :created_at:, :updated_at:)';
        $status = $app->modelsManager->executeQuery($phql, array(
            'author_id' => (int) $author_id,
            'recipient_id' => (int) $recipient_id,
            'text' => htmlentities(strip_tags(trim($message->text)), ENT_QUOTES, 'UTF-8'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        if ($status->success())
        {
            $content = [
                'code' => 201,
                'status' => 'Created',
                'data' => $status->getModel(),
            ];
        }
        else
        {
            $errors = array();
            foreach ($status->getMessages() as $message)
            {
                $errors[] = $message->getMessage();
            }
            $content = [
                'code' => 409,
                'status' => 'Conflict',
                'data' => $errors,
            ];
        }
    }
    
    $response->setJsonContent($content);
    return $response;
});

$app->post('/api/friends/{user_id:[0-9]+}/{friend_id:[0-9]+}', function ($user_id, $friend_id) use ($app) {
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    $user = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $user_id]]);
    if(empty($user))
    {
        $content['data'][] = 'There is no user with such user id.';
    }
    
    $friend = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $friend_id]]);
    if(empty($friend))
    {
        $content['data'][] = 'There is no user with such friend id.';
    }
    
    if(empty($content['data']))
    {
        $friends = UsersFriends::findFirst(['conditions' => 'user_id = ?1 AND friend_id=?2', 
            'bind' => [1 => $friend_id, 2 => $user_id]]);
        $status_value = !empty($friends) ? UsersFriends::FRIEND_STATUS : UsersFriends::SUBSCRIBER_STATUS;
        if($status_value === UsersFriends::FRIEND_STATUS)
        {
            $phql = "UPDATE UsersFriends SET status=:status: WHERE user_id = :user_id: AND friend_id = :friend_id:";
            $status = $app->modelsManager->executeQuery($phql, [
                'status' => $status_value,
                'user_id' => $friend_id,
                'friend_id' => $user_id,
            ]);
            if (!$status->success())
            {
                $errors = array();
                foreach ($status->getMessages() as $message)
                {
                    $errors[] = $message->getMessage();
                }
                $content = [
                    'code' => 409,
                    'status' => 'Conflict',
                    'data' => $errors,
                ];
                $response->setJsonContent($content);
                return $response;
            }
        }
        $phql = 'INSERT INTO UsersFriends (user_id, friend_id, status, created_at, updated_at) ' . 
            'VALUES (:user_id:, :friend_id:, :status:, :created_at:, :updated_at:)';
        $status = $app->modelsManager->executeQuery($phql, array(
            'user_id' => (int) $user_id,
            'friend_id' => (int) $friend_id,
            'status' => $status_value,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        if ($status->success())
        {
            $content = [
                'code' => 201,
                'status' => 'Created',
                'data' => $status->getModel(),
            ];
        }
        else
        {
            $errors = array();
            foreach ($status->getMessages() as $message)
            {
                $errors[] = $message->getMessage();
            }
            $content = [
                'code' => 409,
                'status' => 'Conflict',
                'data' => $errors,
            ];
        }
    }
    
    $response->setJsonContent($content);
    return $response;
});

$app->get('/api/friends/{id:[0-9]+}', function ($id) use ($app) {
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    $user = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $id]]);
    if(empty($user))
    {
        $content['data'][] = 'There is no such user.';
    }
    
    if(empty($content['data']))
    {
        $phql = "SELECT User.* FROM User INNER JOIN UsersFriends ON User.id=UsersFriends.friend_id WHERE UsersFriends.user_id = :id:";
        $friends = $app->modelsManager->executeQuery($phql, ['id' => $id]);
        $content = ['code' => 200, 'status' => 'Ok', 'data' => []];
        foreach ($friends as $friend)
        {
            $content['data'][] = [
                'id' => $friend->id,
                'email' => $friend->email,
                'first_name' => $friend->first_name,
                'second_name' => $friend->second_name,
            ];
        }
    }
    
    $response->setJsonContent($content);
    return $response;
});

$app->handle();