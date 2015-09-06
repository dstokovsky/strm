<?php

use Phalcon\Http\Response;

/**
 * Adds a friend to the given user.
 */
$app->post('/api/friends/{user_id:[0-9]+}/{friend_id:[0-9]+}', function ($user_id, $friend_id) use ($app) {
    $response = new Response();
    $content = ['data' => []];
    $user = User::findFirst(['conditions' => 'id = ?0', 'bind' => [$user_id]]);
    if(empty($user))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no user with such user id.';
    }
    
    $friend = User::findFirst(['conditions' => 'id = ?0', 'bind' => [$friend_id]]);
    if(empty($friend))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no user with such friend id.';
    }
    
    if(empty($content['data']))
    {
        $phql = 'INSERT INTO UsersFriends (user_id, friend_id, created_at, updated_at) ' . 
            'VALUES (:user_id:, :friend_id:, :created_at:, :updated_at:)';
        $status = $app->modelsManager->executeQuery($phql, array(
            'user_id' => (int) $user_id,
            'friend_id' => (int) $friend_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        if ($status->success())
        {
            $response->setStatusCode(201, 'Created');
            $content = [
                'data' => $status->getModel(),
            ];
        }
        else
        {
            foreach ($status->getMessages() as $message)
            {
                $content['data'][] = $message->getMessage();
            }
            $response->setStatusCode(409, 'Conflict');
        }
    }
    
    $response->setJsonContent($content);
    return $response;
});

/**
 * Returns all users which are followed by current user.
 */
$app->get('/api/friends/{id:[0-9]+}/follows', function ($id) use ($app) {
    $response = new Response();
    $content = ['data' => []];
    $user = User::findFirst(['conditions' => 'id = ?0', 'bind' => [(int) $id]]);
    if(empty($user))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no such user.';
    }
    
    if(empty($content['data']))
    {
        $request = $app->request->getJsonRawBody();
        $phql = "SELECT User.* FROM User INNER JOIN UsersFriends ON User.id=UsersFriends.friend_id WHERE UsersFriends.user_id = :id:";
        $sql_params = ['id' => (int) $id];
        if(isset($request->before_id) && (int) $request->before_id > 0)
        {
            $phql .= ' AND User.id < :before_id:';
            $sql_params['before_id'] = (int) $request->before_id;
        }
        if(isset($request->after_id) && (int) $request->after_id > 0)
        {
            $phql .= ' AND User.id > :after_id:';
            $sql_params['after_id'] = (int) $request->after_id;
        }
        $phql .= ' ORDER BY User.id ASC LIMIT 10';
        $friends = $app->modelsManager->executeQuery($phql, $sql_params);
        $response->setStatusCode(200, 'Ok');
        $content = ['data' => []];
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

/**
 * Returns all followers of current user.
 */
$app->get('/api/friends/{id:[0-9]+}/followers', function ($id) use ($app) {
    $response = new Response();
    $content = ['data' => []];
    $user = User::findFirst(['conditions' => 'id = ?0', 'bind' => [(int) $id]]);
    if(empty($user))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no such user.';
    }
    
    if(empty($content['data']))
    {
        $request = $app->request->getJsonRawBody();
        $phql = "SELECT User.* FROM User INNER JOIN UsersFriends ON User.id=UsersFriends.user_id WHERE UsersFriends.friend_id = :id:";
        $sql_params = ['id' => (int) $id];
        if(isset($request->before_id) && (int) $request->before_id > 0)
        {
            $phql .= ' AND User.id < :before_id:';
            $sql_params['before_id'] = (int) $request->before_id;
        }
        if(isset($request->after_id) && (int) $request->after_id > 0)
        {
            $phql .= ' AND User.id > :after_id:';
            $sql_params['after_id'] = (int) $request->after_id;
        }
        $phql .= ' ORDER BY User.id ASC LIMIT 10';
        $friends = $app->modelsManager->executeQuery($phql, $sql_params);
        $response->setStatusCode(200, 'Ok');
        $content = ['data' => []];
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

/**
 * Removes a friend for the given user.
 */
$app->delete('/api/friends/{user_id:[0-9]+}/{friend_id:[0-9]+}', function ($user_id, $friend_id) use ($app) {
    $response = new Response();
    $content = ['data' => []];
    $phql = "DELETE FROM UsersFriends WHERE user_id = :user_id: AND friend_id = :friend_id:";
    $status = $app->modelsManager->executeQuery($phql, ['user_id' => (int) $user_id, 'friend_id' => (int) $friend_id]);
    
    if (!$status->success())
    {
        foreach ($status->getMessages() as $message)
        {
            $content['data'][] = $message->getMessage();
        }
        $response->setStatusCode(409, 'Conflict');
    }
    else
    {
        $response->setStatusCode(200, 'Ok');
    }
    
    $response->setJsonContent($content);
    return $response;
});