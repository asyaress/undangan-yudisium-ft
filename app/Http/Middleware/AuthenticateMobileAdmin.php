<?php

namespace App\Http\Middleware;

use App\Models\MobileDeviceToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! is_string($plain) || $plain === '') {
            return response()->json([
                'message' => 'Token aplikasi tidak ada. Silakan masuk ulang.',
            ], 401);
        }

        $device = MobileDeviceToken::query()
            ->with('user')
            ->where('token_hash', MobileDeviceToken::hashToken($plain))
            ->first();

        $user = $device?->user;

        if (! $user || ! $user->is_admin) {
            return response()->json([
                'message' => 'Sesi aplikasi tidak valid. Silakan masuk ulang.',
            ], 401);
        }

        $device->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('mobileDevice', $device);

        return $next($request);
    }
}
