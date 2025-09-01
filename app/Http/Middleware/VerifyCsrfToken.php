<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/webhook/verify-bills',
        'api/webhook/paystack',
        'api/payment/paystack-callback',
        'admin/login',
        'api/3d',
        'api/webhook/kyc',
        'api/webhook/redbiller',
        'api/webhook/nomba',
        'api/payment/nomba-callback'
    ];
}


