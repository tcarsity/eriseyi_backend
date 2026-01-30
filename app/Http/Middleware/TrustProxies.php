<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * Trust all proxies (recommended)
     */
    protected $proxies = '*';

    /**
     * Headers to trust
     */
    protected $headers =
         Request::HEADER_X_FORWARDED_FOR |
         Request::HEADER_X_FORWARDED_HOST |
         Request::HEADER_X_FORWARDED_PORT |
         Request::HEADER_X_FORWARDED_PROTO;
}
