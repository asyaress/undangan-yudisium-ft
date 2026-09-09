<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileDeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_admin) {
            return response()->json([
                'message' => 'Email atau password salah, atau akun ini bukan panitia.',
            ], 422);
        }

        $issued = MobileDeviceToken::issue($user, $data['device_name'] ?? $request->userAgent());

        return response()->json([
            'token' => $issued['plain'],
            'token_type' => 'Bearer',
            'server_time' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $device = $request->attributes->get('mobileDevice');

        if ($device instanceof MobileDeviceToken) {
            $device->delete();
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
