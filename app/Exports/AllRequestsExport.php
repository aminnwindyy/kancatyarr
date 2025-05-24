<?php

namespace App\Exports;

use App\Models\ProductRequest;
use App\Models\AdvertisementRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Database\Eloquent\Collection;

class AllRequestsExport implements WithMultipleSheets
{
    /**
     * تعریف شیت‌های گزارش
     */
    public function sheets(): array
    {
        return [
            new ProductRequestsSheet(),
            new AdRequestsSheet(),
        ];
    }
}

/**
 * کلاس شیت درخواست‌های محصول
 */
class ProductRequestsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * دریافت داده‌ها
     */
    public function collection()
    {
        return ProductRequest::with('serviceProvider')->orderBy('created_at', 'desc')->get();
    }

    /**
     * تنظیم سرستون‌ها
     */
    public function headings(): array
    {
        return [
            'شناسه',
            'نام خدمات‌دهنده',
            'نام محصول',
            'قیمت',
            'وضعیت',
            'دلیل رد',
            'تاریخ ایجاد',
        ];
    }

    /**
     * نگاشت داده‌ها
     */
    public function map($request): array
    {
        return [
            $request->id,
            $request->serviceProvider->name ?? 'نامشخص',
            $request->product_name,
            $request->price,
            $this->translateStatus($request->status),
            $request->rejection_reason,
            $request->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * نام شیت
     */
    public function title(): string
    {
        return 'درخواست‌های محصول';
    }

    /**
     * ترجمه وضعیت به فارسی
     */
    private function translateStatus($status)
    {
        switch ($status) {
            case 'pending':
                return 'در انتظار بررسی';
            case 'approved':
                return 'تایید شده';
            case 'rejected':
                return 'رد شده';
            default:
                return $status;
        }
    }
}

/**
 * کلاس شیت درخواست‌های تبلیغات
 */
class AdRequestsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * دریافت داده‌ها
     */
    public function collection()
    {
        return AdvertisementRequest::with('serviceProvider')->orderBy('created_at', 'desc')->get();
    }

    /**
     * تنظیم سرستون‌ها
     */
    public function headings(): array
    {
        return [
            'شناسه',
            'نام خدمات‌دهنده',
            'عنوان تبلیغ',
            'وضعیت',
            'موقعیت',
            'دلیل رد',
            'تاریخ شروع',
            'تاریخ پایان',
            'تاریخ ایجاد',
        ];
    }

    /**
     * نگاشت داده‌ها
     */
    public function map($request): array
    {
        return [
            $request->id,
            $request->serviceProvider->name ?? 'نامشخص',
            $request->title,
            $this->translateStatus($request->status),
            $request->position,
            $request->rejection_reason,
            $request->start_date ? $request->start_date->format('Y-m-d') : 'نامشخص',
            $request->end_date ? $request->end_date->format('Y-m-d') : 'نامشخص',
            $request->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * نام شیت
     */
    public function title(): string
    {
        return 'درخواست‌های تبلیغات';
    }

    /**
     * ترجمه وضعیت به فارسی
     */
    private function translateStatus($status)
    {
        switch ($status) {
            case 'pending':
                return 'در انتظار بررسی';
            case 'approved':
                return 'تایید شده';
            case 'rejected':
                return 'رد شده';
            default:
                return $status;
        }
    }
} 