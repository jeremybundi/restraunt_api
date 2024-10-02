<?php

use Phalcon\Mvc\Model;

class Admin extends Model
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $phone_number;
    public $role_id;
    public $otp;
    public $is_verified;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('admins');

        // Define relationships
        $this->belongsTo(
            'role_id', 
            Roles::class, 
            'id', 
            [
                'alias' => 'role',
                'reusable' => true // Cache the role relationship
            ]
        );
    }
}
