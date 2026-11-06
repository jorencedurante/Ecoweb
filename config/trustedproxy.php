<?php

$proxies = env('TRUSTED_PROXIES');

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Reverse Proxies
    |--------------------------------------------------------------------------
    |
    | Laravel only honours a request's X-Forwarded-* headers when the proxy
    | that sent them is listed here (see the framework's TrustProxies
    | middleware, which reads this value at request time). Platforms such as
    | Railway terminate TLS at a reverse proxy in front of PHP, so that proxy
    | must be trusted for Laravel to detect the original https:// request and
    | generate https:// form actions, redirects, links and cookies.
    |
    | When TRUSTED_PROXIES is not set, proxies are trusted automatically only
    | when the application is served over HTTPS (APP_URL starts with
    | https://) or when running on Railway. Local development
    | (APP_URL=http://localhost) therefore trusts no proxies at all and keeps
    | generating http:// URLs exactly as before.
    |
    | Set TRUSTED_PROXIES to override the automatic detection, for example:
    |
    |   TRUSTED_PROXIES=*              trust the immediate upstream proxy
    |   TRUSTED_PROXIES=REMOTE_ADDR    trust the IP that opened the connection
    |   TRUSTED_PROXIES=10.0.0.1,...   trust a comma separated list of IPs
    |
    | Leave it unset to use the automatic detection described above.
    |
    */

    'proxies' => $proxies ?: ((str_starts_with((string) env('APP_URL'), 'https://')
        || env('RAILWAY_ENVIRONMENT_NAME') !== null) ? '*' : null),

];
