<?php

use Phalcon\Http\Response;

/**
 * Get the user profile by its id.
 */
$app->get('/api/users/{id:[0-9]+}', function ($id) use ($app) {
    $phql = "SELECT User.id, User.email, User.account, User.first_name, User.second_name, User.created_at, " . 
        "UserSettings.name, UserSettings.value FROM User LEFT JOIN UserSettings ON User.id=UserSettings.user_id WHERE User.id = :id:";
    $user = $app->modelsManager->executeQuery($phql, ['id' => (int) $id]);
    $content = ['data' => []];
    $is_first_iteration = true;
    foreach ($user as $data)
    {
        if($is_first_iteration)
        {
            $content['data']['id'] = $data->id;
            $content['data']['email'] = $data->email;
            $content['data']['account'] = $data->account;
            $content['data']['first_name'] = html_entity_decode($data->first_name, ENT_QUOTES, 'UTF-8');
            $content['data']['second_name'] = html_entity_decode($data->second_name, ENT_QUOTES, 'UTF-8');
            $content['data']['created_at'] = $data->created_at;
            $is_first_iteration = false;
        }
        
        if(stristr($data->name, ":"))
        {
            $path = explode(":", $data->name);
            $key = $path[0];
            $sub_key = $path[1];
            if(!isset($content['data'][$key]))
            {
                $counter = 0;
                $content['data'][$key] = [];
            }
            
            if(isset($content['data'][$key][$counter][$sub_key]))
            {
                $counter++;
            }
            $content['data'][$key][$counter][$sub_key] = html_entity_decode($data->value, ENT_QUOTES, 'UTF-8');
        }
        elseif(!empty ($data->name))
        {
            $content['data'][$data->name][] = html_entity_decode($data->value, ENT_QUOTES, 'UTF-8');
        }
    }

    // Create a response
    $response = new Response();
    $response->setStatusCode(200, 'Ok');
    if (empty($user))
    {
        $response->setStatusCode(404, 'Not Found');
        $content['data'] = ['There is no such user'];
    }
    
    $response->setJsonContent($content);
    return $response;
});

/**
 * Creates the new user.
 */
$app->post('/api/users', function () use ($app) {
    $user = $app->request->getJsonRawBody();
 
    $phql = 'INSERT INTO User (email, password, account, first_name, second_name, created_at, updated_at) ' . 
        'VALUES (:email:, :password:, :account:, :first_name:, :second_name:, :created_at:, :updated_at:)';
    $status = $app->modelsManager->executeQuery($phql, array(
        'email' => $user->email,
        'password' => $user->password,
        'account' => $user->account,
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
    }
    else
    {
        foreach ($status->getMessages() as $message)
        {
            $content['data'][] = $message->getMessage();
        }
        $response->setStatusCode(409, 'Conflict');
    }
    
    if(!empty($content['data']))
    {
        $response->setJsonContent($content);
        return $response;
    }
    
    $phql = 'INSERT INTO UserSettings (user_id, name, value, created_at, updated_at) VALUES ' . 
        '(:user_id:, :name:, :value:, :created_at:, :updated_at:)';
    foreach ($user as $setting_name => $setting_value)
    {
        if(is_array($setting_value))
        {
            foreach ($setting_value as $values)
            {
                if(is_object($values))
                {
                    foreach ($values as $sub_value_key => $sub_value)
                    {
                        if(is_array($sub_value) || is_object($sub_value))
                        {
                            continue;
                        }
                        $status = $app->modelsManager->executeQuery($phql, [
                            'user_id' => $user->id,
                            'name' => implode(":", [$setting_name, $sub_value_key]),
                            'value' => htmlentities(strip_tags(trim($sub_value)), ENT_QUOTES, 'UTF-8'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        if(!$status->success())
                        {
                            foreach ($status->getMessages() as $message)
                            {
                                $content['data'][] = $message->getMessage();
                            }
                            $response->setStatusCode(409, 'Conflict');
                        }
                    }
                }
                else
                {
                    $status = $app->modelsManager->executeQuery($phql, [
                        'user_id' => $user->id,
                        'name' => $setting_name,
                        'value' => htmlentities(strip_tags(trim($values)), ENT_QUOTES, 'UTF-8'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    if(!$status->success())
                    {
                        foreach ($status->getMessages() as $message)
                        {
                            $content['data'][] = $message->getMessage();
                        }
                        $response->setStatusCode(409, 'Conflict');
                    }
                }
            }
        }
    }
    if(empty($content['data']))
    {
        $content['data'] = $user;
        $response->setStatusCode(201, 'Created');
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
