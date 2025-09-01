<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_name',
        'admin_rate',
        'transfer_number',
        'is_active',
        'description'
    ];

    protected $casts = [
        'admin_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get only active network providers
     */
    public static function active()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * Calculate the total amount user needs to pay including admin rate
     */
    public function calculateUserAmount($airtimeAmount)
    {
        $adminCharge = ($airtimeAmount * $this->admin_rate) / 100;
        return $airtimeAmount + $adminCharge;
    }

    /**
     * Get formatted admin rate for display
     */
    public function getFormattedRateAttribute()
    {
        return number_format($this->admin_rate, 2) . '%';
    }

    /**
     * Get formatted transfer number for display
     */
    public function getFormattedNumberAttribute()
    {
        // Format Nigerian phone numbers
        $number = preg_replace('/[^0-9]/', '', $this->transfer_number);

        if (strlen($number) === 11 && substr($number, 0, 1) === '0') {
            return '+234' . substr($number, 1);
        } elseif (strlen($number) === 10) {
            return '+234' . $number;
        } elseif (strlen($number) === 13 && substr($number, 0, 3) === '234') {
            return '+' . $number;
        }

        return $this->transfer_number;
    }

    /**
     * Scope for filtering by network name
     */
    public function scopeByNetwork($query, $networkName)
    {
        return $query->where('network_name', $networkName);
    }

    /**
     * Get available network options
     */
    public static function getNetworkOptions()
    {
        return [
            'MTN' => 'MTN',
            'GLO' => 'GLO',
            'AIRTEL' => 'AIRTEL',
            '9MOBILE' => '9MOBILE'
        ];
    }

}
