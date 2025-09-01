<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;
    use HasRoles;

    protected $guard_name = 'web';

    #  Set the guard name for this model

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_active',
        'email_verified_at',
        'permissions'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
//        'permissions' => 'array',
    ];

    /**
     * Check if the admin has a specific permission.
     *
     * @param string $permission - The permission key to check (e.g., 'view_dashboard')
     * @return bool - True if the permission exists in the user's permissions array
     */
    /**
     * Check if the admin has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions;

        // Decode JSON if it's a string
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        // Ensure it's an array
        if (!is_array($permissions)) {
            $permissions = [];
        }

        return in_array($permission, $permissions);
    }


    /**
     * Check if the admin has at least one of the given permissions.
     */
    /**
     * Check if the admin has at least one of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPermissions = $this->permissions;

        // Manually decode if it's a JSON string
        if (is_string($userPermissions)) {
            $userPermissions = json_decode($userPermissions, true);
        }

        // If still not an array (null or bad JSON), default to empty array
        if (!is_array($userPermissions)) {
            $userPermissions = [];
        }

        return collect($permissions)->intersect($userPermissions)->isNotEmpty();
    }


    public function hasRole($roles): bool

    {
        $userRole = strtolower($this->role);
        $roles = is_array($roles) ? array_map('strtolower', $roles) : [strtolower($roles)];
        return in_array($userRole, $roles);
    }

    public static function getSuperAdmin()
    {
        return self::where('role', 'super-admin')
            ->get();
    }






}
