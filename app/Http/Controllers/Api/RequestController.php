<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    /**
     * نمایش لیست درخواست‌ها
     * نیاز به مجوز requests.view دارد
     */
    public function index(Request $request)
    {
        // بررسی دسترسی
        if (!$request->user()->can('requests.view')) {
            return response()->json([
                'message' => 'شما دسترسی به این بخش را ندارید.'
            ], 403);
        }

        // در اینجا می‌توانید کد مربوط به دریافت لیست درخواست‌ها را بنویسید
        // این یک نمونه ساده است
        $requests = [
            ['id' => 1, 'title' => 'درخواست شماره 1', 'status' => 'در انتظار بررسی'],
            ['id' => 2, 'title' => 'درخواست شماره 2', 'status' => 'بررسی شده'],
            ['id' => 3, 'title' => 'درخواست شماره 3', 'status' => 'در انتظار بررسی'],
        ];

        return response()->json(['data' => $requests]);
    }

    /**
     * پردازش یک درخواست
     * نیاز به مجوز requests.process دارد
     */
    public function process(Request $request, $id)
    {
        // بررسی دسترسی
        if (!$request->user()->can('requests.process')) {
            return response()->json([
                'message' => 'شما دسترسی به این عملیات را ندارید.'
            ], 403);
        }

        // در اینجا کد مربوط به پردازش درخواست با شناسه مشخص شده را می‌نویسید
        // این یک نمونه ساده است
        return response()->json([
            'message' => "درخواست شماره {$id} با موفقیت پردازش شد."
        ]);
    }

    /**
     * حذف یک درخواست
     * نیاز به مجوز requests.delete دارد
     */
    public function destroy(Request $request, $id)
    {
        // بررسی دسترسی
        if (!$request->user()->can('requests.delete')) {
            return response()->json([
                'message' => 'شما دسترسی به این عملیات را ندارید.'
            ], 403);
        }

        // در اینجا کد مربوط به حذف درخواست با شناسه مشخص شده را می‌نویسید
        // این یک نمونه ساده است
        return response()->json([
            'message' => "درخواست شماره {$id} با موفقیت حذف شد."
        ]);
    }
}
