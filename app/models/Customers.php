<?php

use Phalcon\Mvc\Model;

class Customers extends Model
{
    public $id;
    public $name;
    public $email;
    public $phone_number;
    public $otp;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('customers');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
