<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformRevenue extends Model
{
    use HasFactory;

    protected $table = 'platform_revenues';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'product_name',
        'type',
        'status',
        'amount',
        'unit_price',
        'commission',
        'profit',
        'platform',
        'unique_element',
        'channel',
        'response_code',
        'transaction_date',
        'raw_response',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'raw_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
