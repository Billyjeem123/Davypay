<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserActivityLog extends Model
{
    use HasFactory;


    protected $fillable = [
            'user_id',
            'activity',
            'description',
            'page_url',
            'properties',
            'ip_address',
            'user_agent',
            'created_at'
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

   # Scope for recent activities
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

   # Scope for specific activity types
    public function scopeOfType($query, $activity)
    {
        return $query->where('activity', $activity);
    }


    public static function metrics(): array
    {
        // More efficient: Get most active user's name in a single query
        $mostActiveUserName = self::join('users', 'user_activity_logs.user_id', '=', 'users.id')
            ->select('users.first_name', 'users.last_name')
            ->groupBy('user_activity_logs.user_id', 'users.first_name', 'users.last_name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1)
            ->first();

        return [
            'totalActivities' => self::count(),

            'activeUsers24h' => self::where('created_at', '>=', now()->subDay())
                ->distinct('user_id')
                ->count('user_id'),

            'mostActiveUserName' => $mostActiveUserName ?
                $mostActiveUserName->first_name . ' ' . $mostActiveUserName->last_name : 'N/A',

            'uniqueIps' => self::where('created_at', '>=', now()->subDays(7))
                ->distinct('ip_address')
                ->count('ip_address'),

            'flaggedActivities' => self::where(function ($query) {
                $query->where('activity', 'like', '%unauthorized%')
                    ->orWhere('activity', 'like', '%failed%')
                    ->orWhere('activity', 'like', '%blocked%');
            })->count(),

            'topPage' => optional(self::select('page_url', DB::raw('count(*) as total'))
                ->whereNotNull('page_url')
                ->groupBy('page_url')
                ->orderByDesc('total')
                ->first())->page_url,

            'topActivity' => optional(self::select('activity', DB::raw('count(*) as total'))
                ->groupBy('activity')
                ->orderByDesc('total')
                ->first())->activity,

            'hourlyStats' => self::select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
        ];
    }



}
