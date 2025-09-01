<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionFee extends Model
{
    use HasFactory;


    protected $fillable = [
        'provider',
        'type',
        'min',
        'max',
        'fee',
    ];
}
