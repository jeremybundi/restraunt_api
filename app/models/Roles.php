<?php

use Phalcon\Mvc\Model;

class Roles extends Model
{
    public $id;
    public $role_name;

    public function initialize()
    {
        $this->setSource('admin_roles');

        // Define relationships if necessary
        $this->hasMany(
            'id',
            'Admin',
            'role_id',
            [
                'alias' => 'admins'
            ]
        );
    }

    // Optionally, define any additional logic, such as retrieving roles by name
}
