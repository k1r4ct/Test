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
        //'api/passwordreset',
        'web/passwordreset',
        'passwordreset/*',
        'web/forgot-password',
        'storeLeadExternal',
        'web/storeLeadExternal',
        'api',
        'api/*',
    ];
}
