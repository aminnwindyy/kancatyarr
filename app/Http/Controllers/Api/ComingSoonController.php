<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ComingSoonController extends Controller
{
    /**
     * لیست بخش‌های آتی
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // لیست بخش‌های "بزودی" - به صورت ثابت تعریف شده‌اند
        $comingSoonFeatures = [
            [
                'id' => 'credit',
                'title' => 'اعتبار',
                'description' => 'این بخش در حال پیاده‌سازی می‌باشد',
                'icon' => url('images/coming-soon/credit-icon.png'),
                'image' => url('images/coming-soon/credit.png'),
                'is_active' => true,
                'coming_date' => '1403/05/01', // تاریخ تقریبی راه‌اندازی
            ],
            [
                'id' => 'ai',
                'title' => 'هوش من',
                'description' => 'این بخش در حال پیاده‌سازی می‌باشد',
                'icon' => url('images/coming-soon/ai-icon.png'),
                'image' => url('images/coming-soon/ai.png'),
                'is_active' => true,
                'coming_date' => '1403/06/15', // تاریخ تقریبی راه‌اندازی
            ],
            [
                'id' => 'marketplace',
                'title' => 'فروشگاه محصولات',
                'description' => 'این بخش در حال پیاده‌سازی می‌باشد',
                'icon' => url('images/coming-soon/marketplace-icon.png'),
                'image' => url('images/coming-soon/marketplace.png'),
                'is_active' => true,
                'coming_date' => '1403/07/30', // تاریخ تقریبی راه‌اندازی
            ],
            // می‌توانید بخش‌های بیشتری اضافه کنید
        ];

        return response()->json([
            'status' => 'success',
            'data' => $comingSoonFeatures,
        ]);
    }

    /**
     * دریافت اطلاعات یک بخش آتی
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // لیست بخش‌های "بزودی" - به صورت ثابت تعریف شده‌اند
        $comingSoonFeatures = [
            'credit' => [
                'id' => 'credit',
                'title' => 'اعتبار',
                'description' => 'این بخش در حال پیاده‌سازی می‌باشد',
                'full_description' => 'بخش اعتبار به شما امکان می‌دهد با خرید اعتبار، از خدمات سایت با تخفیف ویژه استفاده کنید. این بخش به زودی راه‌اندازی خواهد شد.',
                'icon' => url('images/coming-soon/credit-icon.png'),
                'image' => url('images/coming-soon/credit.png'),
                'banner' => url('images/coming-soon/credit-banner.jpg'),
                'is_active' => true,
                'coming_date' => '1403/05/01', // تاریخ تقریبی راه‌اندازی
                'features' => [
                    'خرید اعتبار با تخفیف ویژه',
                    'استفاده از اعتبار در همه بخش‌های سایت',
                    'امکان انتقال اعتبار به دوستان',
                    'دریافت اعتبار هدیه در مناسبت‌های خاص',
                ],
            ],
            'ai' => [
                'id' => 'ai',
                'title' => 'هوش من',
                'description' => 'این بخش در حال پیاده‌سازی می‌باشد',
                'full_description' => 'بخش هوش من با استفاده از هوش مصنوعی، خدمات شخصی‌سازی شده به شما ارائه می‌دهد. این بخش به زودی راه‌اندازی خواهد شد.',
                'icon' => url('images/coming-soon/ai-icon.png'),
                'image' => url('images/coming-soon/ai.png'),
                'banner' => url('images/coming-soon/ai-banner.jpg'),
                'is_active' => true,
                'coming_date' => '1403/06/15', // تاریخ تقریبی راه‌اندازی
                'features' => [
                    'پیشنهاد خدمات متناسب با نیاز شما',
                    'پاسخگویی هوشمند به سوالات',
                    'تحلیل هوشمند نیازهای شما',
                    'ارائه راهکارهای شخصی‌سازی شده',
                ],
            ],
            'marketplace' => [
                'id' => 'marketplace',
                'title' => 'فروشگاه محصولات',
                'description' => 'این بخش در حال پیاده‌سازی می‌باشد',
                'full_description' => 'در فروشگاه محصولات می‌توانید محصولات متنوعی را خریداری کنید. این بخش به زودی راه‌اندازی خواهد شد.',
                'icon' => url('images/coming-soon/marketplace-icon.png'),
                'image' => url('images/coming-soon/marketplace.png'),
                'banner' => url('images/coming-soon/marketplace-banner.jpg'),
                'is_active' => true,
                'coming_date' => '1403/07/30', // تاریخ تقریبی راه‌اندازی
                'features' => [
                    'خرید آنلاین محصولات',
                    'تنوع گسترده محصولات',
                    'قیمت‌های رقابتی',
                    'ارسال سریع به سراسر کشور',
                ],
            ],
        ];

        // بررسی وجود بخش مورد نظر
        if (!isset($comingSoonFeatures[$id])) {
            return response()->json([
                'status' => 'error',
                'message' => 'بخش مورد نظر یافت نشد',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $comingSoonFeatures[$id],
        ]);
    }

    /**
     * ثبت بازدید کاربر از بخش آتی
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackVisit(Request $request, $id)
    {
        // در اینجا می‌توانید بازدید کاربر را ثبت کنید
        // برای مثال، می‌توانید از سیستم لاگ یا آنالیتیکس استفاده کنید
        
        \Log::info("User visited coming soon feature: {$id}", [
            'user_id' => $request->user()->id ?? 'guest',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'بازدید شما ثبت شد',
        ]);
    }
}
