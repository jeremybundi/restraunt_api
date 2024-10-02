<?php

use Phalcon\Mvc\Model;

class TableReservations extends Model
{
    public $id;
    public $customer_id;
    public $table_id;
    public $reservation_date;
    public $start_time;
    public $end_time;
    public $price_per_hour;
    public $number_of_hours;
    public $amount;
    public $total_amount;
    public $status;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('table_reservations');
    }

    public function beforeSave()
    {
        // Set created_at and updated_at to the current timestamp in the correct format
        if (!$this->id) {  // Check if id is not set, meaning it's a new record
            $this->created_at = date('Y-m-d H:i:s'); // Current timestamp
        }
        $this->updated_at = date('Y-m-d H:i:s'); // Current timestamp for updates
    }
}
