<?php

use Phalcon\Mvc\Model;

class Table extends Model
{
    public $id;
    public $table_number;
    public $image_url;
    public $capacity;
    public $deposit_per_hour;
    public $status;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('tables');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
