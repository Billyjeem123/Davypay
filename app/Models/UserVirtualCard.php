<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVirtualCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_id',
        'card_status',
        'name',
        'brand',
        'type',
        'reference',
        'customer_id',
        'provider_user_id',
        'api_response',
        'status'
    ];

    protected $casts = [
        'api_response' => 'array', // automatically decode JSON
    ];

    protected $hidden = [
        'api_response'
    ];

    /**
     * Relationship: Each card belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
