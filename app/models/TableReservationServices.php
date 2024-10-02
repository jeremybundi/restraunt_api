<?php

use Phalcon\Mvc\Model;

class TableReservationServices extends Model
{
    public $id;
    public $reservation_id;
    public $service_id;
    public $number_of_times;
    public $price;
    public $amount;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('table_reservation_services');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
