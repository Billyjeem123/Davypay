<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Esim extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',

        // SIM details
        'sim_id',
        'iccid',
        'product_id',
        'imsi',
        'state',
        'last_operation_date',
        'activation_code',
        'smdp',
        'purchase_date',

        // Data Plan details
        'plan_product_id',
        'plan_name',
        'data_usage_allowance',
        'time_allowance',
        'country',
        'iso3',
        'region',

        // Response info
        'status',
        'response_code',
        'response_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
