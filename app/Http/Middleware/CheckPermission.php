<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * مدیریت درخواست ورودی
     * بررسی می‌کند که آیا کاربر مجوز مورد نیاز را دارد یا خیر
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user() || ! $request->user()->can($permission)) {
            return response()->json([
                'message' => 'شما دسترسی به این بخش را ندارید.'
            ], 403);
        }

        return $next($request);
    }
}
