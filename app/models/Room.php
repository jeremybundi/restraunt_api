<?php

use Phalcon\Mvc\Model;

class Room extends Model
{
    public $id;
    public $image_url;
    public $room_number;
    public $room_type;
    public $capacity;
    public $price_per_night;
    public $status;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('rooms');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
