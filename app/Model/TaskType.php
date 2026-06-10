<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    protected $table = 'task_types';
    protected $fillable = ['name', 'client_id'];

    const TASK_TYPE_NAME_ID  = [
        'Pickup'        => 1,
        'Drop-off'      => 2,
        'Appointment'   => 3,
    ];


    public function getNameAttribute($value)
    {
        if($value == 'Drop-off')
        return 'Drop';
        else
        return $value;
    }
}
