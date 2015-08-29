<?php

use Phalcon\Http\Response;

/**
 * Get the user profile by its id.
 */
$app->get('/api/users/{id:[0-9]+}', function ($id) use ($app) {
    $phql = "SELECT * FROM User WHERE id = :id:";
    $user = $app->modelsManager->executeQuery($phql, ['id' => (int) $id])->getFirst();

    // Create a response
    $response = new Response();
    $content = ['data' => []];
    if (!empty($user))
    {
        $response->setStatusCode(200, 'Ok');
        $content = [
            'data'   => [
                'id'   => $user->id,
                'name' => $user->email,
                'first_name' => html_entity_decode($user->first_name, ENT_QUOTES, 'UTF-8'),
                'second_name' => html_entity_decode($user->second_name, ENT_QUOTES, 'UTF-8'),
                'created_at' => $user->created_at,
            ]
        ];
    }
    else
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'][] = 'There is no such user';
    }
    $response->setJsonContent($content);

    return $response;
});

/**
 * Creates the new user.
 */
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
        $response->setStatusCode(201, 'Created');
        $user->id = $status->getModel()->id;
        $content = [
            'data' => $user,
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
    $response->setJsonContent($content);

    return $response;
});

/**
 * Updates the profile of given user.
 */
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
        $content = ['data' => $status->getModel()];
    }
    $response->setJsonContent($content);

    return $response;
});

/**
 * Removes the user by given id.
 */
$app->delete('/api/users/{id:[0-9]+}', function ($id) use ($app) {
    $phql = "DELETE FROM User WHERE id = :id:";
    $status = $app->modelsManager->executeQuery($phql, ['id' => (int) $id]);

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
