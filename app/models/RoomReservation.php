<?php

use Phalcon\Mvc\Model;

class RoomReservation extends Model
{
    public $id;
    public $customer_id;
    public $room_id;
    public $check_in;
    public $check_out;
    public $price_per_day;
    public $number_of_days;
    public $amount;
    public $total_amount;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('room_reservations');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
