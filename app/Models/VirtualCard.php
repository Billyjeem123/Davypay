<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VirtualCard extends Model
{
    use HasFactory;

    protected $table = 'virtual_cards';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'state',
        'city',
        'address',
        'zip_code',
        'id_type',
        'id_number',
        'user_id',
        'provider_user_id',
        'card_status',
        'api_response',
        'provider',
        'card_id',
        'security_code',
        'expiration',
        'currency',
        'status',
        'is_physical',
        'title',
        'color',
        'name',
        'balance',
        'type',
        'brand',
        'mask',
        'number',
        'owner_id',
        'last_used_on',
        'is_non_subscription',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip_code',
        'billing_country',
        'card_type',
        'card_brand',
        'card_user_id',
        'reference',
        'customer_id',
    ];

    protected $casts = [
        'api_response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'id_number',
        'api_response',
    ];

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isActive(): bool
    {
        return $this->card_status === 'active';

    }

    public static function add_to_wallet(float $amount): ?float
    {
        $user = Auth::user();
        $balance = self::where('user_id', $user->id)->first();

        if (!$balance) {
            return null;
        }

        $balance->balance += $amount;
        $balance->save();

        return $balance->balance;
    }

}
