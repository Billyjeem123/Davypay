<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'account_number',
        'bank_name',
        'account_name',
        'provider',
        'raw_response',
        'wallet_id',
        'account_ref',
        'account_holder_id',
        'bvn',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];


    public function user(){

        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallet(){

        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}
