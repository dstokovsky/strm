<?php

use Phalcon\Http\Response;

/**
 * Adds user with banned user id into user's id blacklist.
 */
$app->post('/api/blacklist/{user_id:[0-9]+}/{banned_user_id:[0-9]+}', function ($user_id, $banned_user_id) use ($app) {
    $response = new Response();
    $content = ['data' => []];
    $user = User::findFirst(['conditions' => 'id = ?0', 'bind' => [(int) $user_id]]);
    if(empty($user))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no user with such user id.';
    }
    
    $banned_user = User::findFirst(['conditions' => 'id = ?0', 'bind' => [(int) $banned_user_id]]);
    if(empty($banned_user))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no user matching user id for blocking.';
    }
    
    if(empty($content['data']))
    {
        $phql = 'INSERT INTO Blacklist (user_id, banned_user_id, created_at, updated_at) ' . 
            'VALUES (:user_id:, :banned_user_id:, :created_at:, :updated_at:)';

        $status = $app->modelsManager->executeQuery($phql, array(
            'user_id' => (int) $user_id,
            'banned_user_id' => (int) $banned_user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        if ($status->success())
        {
            $response->setStatusCode(201, 'Created');
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
 * Removes user with banned user id from user's id blacklist.
 */
$app->delete('/api/blacklist/{user_id:[0-9]+}/{banned_user_id:[0-9]+}', function ($user_id, $banned_user_id) use ($app) {
    $phql = "DELETE FROM Blacklist WHERE user_id = :user_id: AND banned_user_id = :banned_user_id:";
    $status = $app->modelsManager->executeQuery($phql, ['user_id' => (int) $user_id, 
        'banned_user_id' => (int) $banned_user_id]);

    $response = new Response();
    $content = ['data' => []];
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
