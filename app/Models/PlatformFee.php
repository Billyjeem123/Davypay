<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'fee_amount',
        'fee_percentage',
        'provider',
        'transaction_type',
        'fee_rule_id',
    ];


    public function user(){

        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction(){

        return $this->belongsTo(TransactionLog::class, 'transaction_id');
    }
}
