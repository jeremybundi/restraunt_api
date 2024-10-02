<?php

use Phalcon\Mvc\Model;

class Service extends Model
{
    public $id;
    public $service_name;
    public $service_type;
    public $price;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('services');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
