<?php

namespace App\Exports;

use App\Models\DiscountRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DiscountRequestsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $status;
    protected $period;

    public function __construct($status = null, $period = null)
    {
        $this->status = $status;
        $this->period = $period;
    }

    /**
     * ایجاد کوئری برای گرفتن داده‌ها
     */
    public function query()
    {
        $query = DiscountRequest::query()->with([
            'serviceProvider:id,name',
            'product:product_id,name,price'
        ]);

        // اعمال فیلتر وضعیت
        if ($this->status) {
            $query->ofStatus($this->status);
        }

        // اعمال فیلتر دوره زمانی
        if ($this->period) {
            switch ($this->period) {
                case 'daily':
                    $query->today();
                    break;
                case 'weekly':
                    $query->thisWeek();
                    break;
                case 'monthly':
                    $query->thisMonth();
                    break;
            }
        }

        return $query->orderBy('created_at', 'desc');
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
            'قیمت اصلی',
            'درصد تخفیف',
            'قیمت با تخفیف',
            'تاریخ شروع',
            'تاریخ پایان',
            'وضعیت',
            'تاریخ درخواست'
        ];
    }

    /**
     * نگاشت داده‌ها
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->serviceProvider->name ?? 'نامشخص',
            $row->product->name ?? 'نامشخص',
            $row->product->price ?? 0,
            $row->discount_percentage,
            ($row->product->price ?? 0) * (1 - $row->discount_percentage / 100),
            $row->start_date->format('Y-m-d'),
            $row->end_date->format('Y-m-d'),
            $row->status,
            $row->created_at->format('Y-m-d')
        ];
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