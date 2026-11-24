<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense in depth for certificate output routes. The same explicit grant is
 * also checked inside CertificateController so direct controller calls remain
 * protected even if a route is registered elsewhere.
 */
class EnsureCertificatePrintAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $message = 'Certificate printing access has not been granted. Please contact your administrator.';

        if (!auth()->user()?->canPrintCertificates()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message, 'error' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
