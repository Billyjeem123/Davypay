<?php

namespace App\Models;

#  use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\Utility;
use App\Services\ActivityTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'maiden',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'pin',
        'referral_code',
        'referral_bonus',
        'account_level',
        'remember_token',
        'username',
        'otp',
        'role',
        'kyc_status',
        'kyc_type',
        'device_token',
        'device_type',
        'is_account_restricted',
        'is_ban',
        'view',
        'reason_restriction',
        'restriction_date',
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'has_exceeded_limit' => 'boolean',
    ];

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }


    public function kyc()
    {
        return $this->hasOne(Kyc::class, 'user_id');
    }


    public function virtual_accounts()
    {
        $preferredProvider = Utility::getSetting('preferred_provider');

        return $this->hasMany(VirtualAccount::class, 'user_id')
            ->select('id','user_id', 'account_name', 'bank_name', 'account_number', 'provider')
            ->when($preferredProvider, function ($query, $provider) {
                return $query->where('provider', $provider);
            });
    }

    public function routeNotificationForFcm()
    {
        return $this->device_token;
    }


    public static function findByEmailOrAccountNumber(string $identifier)
    {
        return self::where('email', $identifier)
            ->orWhereHas('virtual_accounts', function ($query) use ($identifier) {
                $query->where('account_number', $identifier);
            })
            ->first();
    }


    public function transactions()
    {
        return $this->hasMany(TransactionLog::class, 'user_id');
    }


    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function activity_logs()
    {
        return $this->hasMany(UserActivityLog::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function referredBy()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public static function generateUniqueReferralCode(string $firstName, string $lastName): string
    {
        do {
            $code = strtoupper(substr($firstName, 0, 3) . substr($lastName, 0, 3) . rand(1000, 9999));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function getReferralLink(): array
    {
        return [
            'referral_code' => $this->referral_code,
            'ios_link' => "https:# apps.apple.com/app/yourapp?ref={$this->referral_code}",
            'android_link' => "https:# play.google.com/store/apps/details?id=com.yourapp&ref={$this->referral_code}",
            'web_link' => url("/register?ref={$this->referral_code}")
        ];
    }

    public function getReferralStats(): array
    {
        $totalReferrals = $this->referrals()->count();
        $completedReferrals = $this->referrals()->completed()->count();
        $pendingReferrals = $this->referrals()->pending()->count();
        $totalEarnings = $this->referrals()->completed()->sum('reward_amount');

        return [
            'total_referrals' => $totalReferrals,
            'completed_referrals' => $completedReferrals,
            'pending_referrals' => $pendingReferrals,
            'total_earnings' => $totalEarnings,
            'referral_code' => $this->referral_code
        ];
    }


    public function tier()
    {
        return $this->hasOne(Tier::class, 'name', 'account_level');
    }



    public function virtual_cards()
    {
        $preferredProvider = Utility::getSetting('preferred_virtual_card_provider', 'strowallet');

        return $this->hasMany(VirtualCard::class, 'user_id')
            ->when($preferredProvider, function ($query, $provider) {
                return $query->where('provider', $provider);
            });
    }




    public static function getWalletIdByUserId($userId)
    {
        $user = self::find($userId);

        if (!$user || !$user->wallet) {
            return null; #  or throw an exception if preferred
        }

        return $user->wallet->id;
    }


    #  In app/Models/User.php
    public static function registeredTodayCount(): int
    {
        return static::whereDate('created_at', today())->count();
    }



    public function scopeRestricted($query)
    {
        return $query->where('is_account_restricted', true);
    }

    public function scopeBanned($query)
    {
        return $query->where('is_ban', true);
    }

    public function scopeRecentSignups($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }


    #  In User model
    public static function dashboardUsers()
    {
        return static::select('id', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'is_account_restricted', 'is_ban')
            ->latest()
            ->get();
    }

    #  Relationship for UserDevices
    public function userDevice()
    {
        return $this->hasOne(UserDevice::class)->where('is_active', true);
    }

    public function allDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Register device for user
     */


    public  function registerDevice001( $user, array $validatedData): void
    {
        if (!isset($validatedData['device_id'])) {
            return;
        }

        $deviceId = $validatedData['device_id'];

        if (UserDevice::isDeviceInUse($deviceId, $user->id)) {
            throw new \Exception('This device is already registered to another account');
            // Or return error response if this is called from a controller
        }

        $deviceInfo = [
            'device_fingerprint' => $validatedData['device_fingerprint'] ?? null,
            'device_name' => $validatedData['device_name'] ?? null,
            'device_type' => $validatedData['device_type'] ?? null,
            'device_token' => $validatedData['device_token'] ?? null,
            'platform' => $validatedData['platform'] ?? null,
            'app_version' => $validatedData['app_version'] ?? null,
        ];

        $user->registerDevice($deviceId, $deviceInfo);
    }
    public function registerDevice($deviceId, $deviceInfo = [])
    {
        #  First deactivate any existing devices for this user
        $this->allDevices()->update(['is_active' => false]);

        #  Use updateOrCreate to handle existing devices
        return $this->allDevices()->updateOrCreate(
            ['device_id' => $deviceId], #  Find existing device by device_id
            [
                #  Update/Create with this data
                'device_fingerprint' => $deviceInfo['device_fingerprint'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'device_token' => $deviceInfo['device_token'] ?? null,
                'platform' => $deviceInfo['platform'] ?? null,
                'app_version' => $deviceInfo['app_version'] ?? null,
                'is_active' => true,
                'last_active_at' => now()
            ]
        );
    }

    /**
     * Check if user can login from this device
     */
    public function canLoginFromDevice($deviceId)
    {
        $activeDevice = $this->userDevice;
        if (!$activeDevice) {
            return true;
        }
        return $activeDevice->device_id === $deviceId;
    }


    public function allVirtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class, 'user_id')
            ->select('id','user_id', 'account_name', 'bank_name', 'account_number', 'provider');
    }



}
