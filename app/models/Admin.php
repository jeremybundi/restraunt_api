<?php

use Phalcon\Mvc\Model;

class Admin extends Model
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $phone_number;
    public $otp;
    public $role_id;
    public $is_verified;

    public function initialize()
    {
        $this->setSource('admins');
    }
}
