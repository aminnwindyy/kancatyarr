<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountSecurityController extends Controller
{
    /**
     * Get user login settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginSettings()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'login_preference' => $user->login_preference,
                'has_verified_email' => !is_null($user->email_verified_at),
                'has_verified_phone' => !is_null($user->phone_verified_at),
                'email' => $user->email,
                'phone_number' => $user->phone_number
            ]
        ]);
    }

    /**
     * Update user login settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLoginSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'login_preference' => ['required', Rule::in(['password', 'email_otp', 'phone_otp'])],
        ]);

        $loginPreference = $request->login_preference;

        // Check if email is verified when selecting email_otp
        if ($loginPreference === 'email_otp' && is_null($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'برای انتخاب ورود با ایمیل، باید ابتدا ایمیل خود را تایید کنید.'
            ], 422);
        }

        // Check if phone is verified when selecting phone_otp
        if ($loginPreference === 'phone_otp' && is_null($user->phone_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'برای انتخاب ورود با شماره تلفن، باید ابتدا شماره تلفن خود را تایید کنید.'
            ], 422);
        }

        $user->login_preference = $loginPreference;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تنظیمات ورود با موفقیت به‌روزرسانی شد.',
            'data' => [
                'login_preference' => $user->login_preference
            ]
        ]);
    }

    /**
     * Get user login history (devices)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDevices(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $loginHistories = Auth::user()->loginHistories()->latest('login_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $loginHistories
        ]);
    }
}
