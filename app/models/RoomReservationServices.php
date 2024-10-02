<?php

use Phalcon\Mvc\Model;

class RoomReservationServices extends Model
{
    public $id;
    public $reservation_id;
    public $service_id;
    public $price;
    public $number_of_times;
    public $amount;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('room_reservation_services');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
