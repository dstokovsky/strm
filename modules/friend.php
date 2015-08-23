<?php

use Phalcon\Http\Response;

/**
 * Adds a friend to the given user.
 */
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

/**
 * Returns all users which are followed by current user.
 */
$app->get('/api/friends/{id:[0-9]+}/follows', function ($id) use ($app) {
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    $user = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $id]]);
    if(empty($user))
    {
        $content['data'][] = 'There is no such user.';
    }
    
    if(empty($content['data']))
    {
        $phql = "SELECT User.* FROM User INNER JOIN UsersFriends ON User.id=UsersFriends.friend_id WHERE UsersFriends.user_id = :id: LIMIT 10";
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

/**
 * Returns all followers of current user.
 */
$app->get('/api/friends/{id:[0-9]+}/followers', function ($id) use ($app) {
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    $user = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $id]]);
    if(empty($user))
    {
        $content['data'][] = 'There is no such user.';
    }
    
    if(empty($content['data']))
    {
        $phql = "SELECT User.* FROM User INNER JOIN UsersFriends ON User.id=UsersFriends.user_id WHERE UsersFriends.friend_id = :id: LIMIT 10";
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
