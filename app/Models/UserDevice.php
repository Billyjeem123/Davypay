<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id', 'device_id', 'device_fingerprint', 'device_name',
        'device_type', 'platform', 'app_version',
        'is_active', 'last_active_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_active_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if another user is already using this device
     */
    public static function isDeviceInUse($deviceId, $excludeUserId = null)
    {
        $query = self::where('device_id', $deviceId)->where('is_active', true);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Get the user currently using this device
     */
    public static function getCurrentDeviceUser($deviceId)
    {
        $device = self::where('device_id', $deviceId)->where('is_active', true)->first();
        return $device ? $device->user : null;
    }
}
