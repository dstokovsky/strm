<?php

use Phalcon\Mvc\Model\Validator\Uniqueness as Uniqueness;

class UsersFriends extends \Phalcon\Mvc\Model
{
    
    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var integer
     */
    public $friend_id;

    /**
     *
     * @var string
     */
    public $created_at;

    /**
     *
     * @var string
     */
    public $updated_at;

    /**
     * Validations and business logic
     *
     * @return boolean
     */
    public function validation()
    {   
        $this->validate(
            new Uniqueness(
                array(
                    "field"   => ['user_id', 'friend_id'],
                    "message" => 'Relations between users must be unique'
                )
            )
        );

        if ($this->validationHasFailed() == true) {
            return false;
        }

        return true;
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'users_friends';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return UsersFriends[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return UsersFriends
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
