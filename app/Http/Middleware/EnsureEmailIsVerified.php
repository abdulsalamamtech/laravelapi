<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {

            // return response()->json(['message' => 'Your email address is not verified.'], 409);

            $response['success'] = false;
            $response['message'] = 'Your email address is not verified.';
            $statusCode = 409;

            // return response()->json($response, $statusCode);
            return redirect()->away(config('app.frontend_verify_email_error_url') . '?message=email_not_verified&status=409');

        }

        return $next($request);
    }
}
