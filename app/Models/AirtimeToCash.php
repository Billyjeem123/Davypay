<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeToCash extends Model
{
    use HasFactory;

    protected $table = 'airtime_to_cash';

    protected  $guarded = [];


    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function network()
    {
        return $this->belongsTo(NetworkProvider::class, 'network_provider_id');

    }

    public  function user(){

        return $this->belongsTo(User::class, 'user_id')->select(['id', 'first_name', 'last_name']);
    }

}
