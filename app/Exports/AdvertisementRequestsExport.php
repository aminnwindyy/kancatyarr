<?php

namespace App\Exports;

use App\Models\AdvertisementRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdvertisementRequestsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $status;
    protected $period;

    public function __construct($status = null, $period = null)
    {
        $this->status = $status;
        $this->period = $period;
    }

    public function query()
    {
        $query = AdvertisementRequest::query()->with([
            'serviceProvider:id,name'
        ]);

        // فیلتر بر اساس وضعیت
        if ($this->status) {
            $query->ofStatus($this->status);
        }

        // فیلتر بر اساس دوره زمانی
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

    public function headings(): array
    {
        return [
            'شناسه',
            'نام خدمات‌دهنده',
            'عنوان تبلیغ',
            'تاریخ شروع',
            'تاریخ پایان',
            'قیمت',
            'ویژه',
            'وضعیت',
            'تاریخ درخواست'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->serviceProvider->name ?? 'نامشخص',
            $row->title,
            $row->start_date->format('Y-m-d'),
            $row->end_date->format('Y-m-d'),
            $row->price,
            $row->is_featured ? 'بله' : 'خیر',
            $row->status,
            $row->created_at->format('Y-m-d')
        ];
    }
} 