<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AgentPayment extends Model
{
    protected $table = 'payments';

    protected $fillable = ['driver_id', 'cr', 'dr','payment_from'];
}
