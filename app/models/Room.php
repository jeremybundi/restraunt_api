<?php

use Phalcon\Mvc\Model;

class Room extends Model
{
    public $id;
    public $room_number;
    public $room_type;
    public $capacity;
    public $image_url;
    public $price;
    public $status;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('rooms');
    }
}
