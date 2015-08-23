<?php

use Phalcon\Http\Response;

/**
 * Sends a message from author to recipient.
 */
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

/**
 * Returns all users with which current user had some communications.
 */
$app->get('/api/messages/{user_id:[0-9]+}/history', function ($user_id) use ($app) {
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    
    $user = User::findFirst(['conditions' => 'id = ?1', 'bind' => [1 => $user_id]]);
    if(empty($user))
    {
        $content['data'][] = 'There is no user with such id.';
    }
    
    if(empty($content['data']))
    {
        $phql = "SELECT User.* FROM User INNER JOIN Message ON User.id=Message.recipient_id WHERE Message.author_id = :id: LIMIT 10";
        $as_author = $app->modelsManager->executeQuery($phql, ['id' => $user_id]);
        $content = ['code' => 200, 'status' => 'Ok', 'data' => []];
        $users = [];
        foreach ($as_author as $recipient)
        {
            $users[$recipient->id] = [
                'id' => $recipient->id,
                'email' => $recipient->email,
                'first_name' => $recipient->first_name,
                'second_name' => $recipient->second_name,
            ];
        }
        
        $phql = "SELECT User.* FROM User INNER JOIN Message ON User.id=Message.author_id WHERE Message.recipient_id = :id: LIMIT 10";
        $as_recipient = $app->modelsManager->executeQuery($phql, ['id' => $user_id]);
        foreach ($as_recipient as $author)
        {
            $users[$author->id] = [
                'id' => $author->id,
                'email' => $author->email,
                'first_name' => $author->first_name,
                'second_name' => $author->second_name,
            ];
        }
        
        $content['data'] = array_values($users);
    }
    
    $response->setJsonContent($content);
    return $response;
});

/**
 * Returns the chat history between author and recipient.
 */
$app->get('/api/messages/{author_id:[0-9]+}/{recipient_id:[0-9]+}', function ($author_id, $recipient_id) use ($app) {
    $response = new Response();
    $content = ['code' => 404, 'status' => 'Not Found', 'data' => []];
    
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
        $phql = "SELECT id, author_id, text, created_at, updated_at " . 
            "FROM Message " . 
            "WHERE (author_id = :author_id: AND recipient_id = :recipient_id:) OR " .
            "(author_id = :recipient_id: AND recipient_id = :author_id:) LIMIT 10";
        $messages = $app->modelsManager->executeQuery($phql, ['author_id' => $author_id, 'recipient_id' => $recipient_id]);
        $content = ['code' => 200, 'status' => 'Ok', 'data' => []];
        foreach ($messages as $message)
        {
            $content['data'][] = $message;
        }
    }
    
    $response->setJsonContent($content);
    return $response;
});
