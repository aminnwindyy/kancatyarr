<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceProviderDetailsExport implements WithMultipleSheets
{
    protected $serviceProvider;
    protected $activityChart;

    public function __construct($serviceProvider, $activityChart)
    {
        $this->serviceProvider = $serviceProvider;
        $this->activityChart = $activityChart;
    }

    public function sheets(): array
    {
        $sheets = [
            new ServiceProviderInfoSheet($this->serviceProvider),
            new ServiceProviderActivitiesSheet($this->serviceProvider),
            new ServiceProviderOrdersSheet($this->serviceProvider),
            new ServiceProviderChartDataSheet($this->activityChart)
        ];

        return $sheets;
    }
}

class ServiceProviderInfoSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $serviceProvider;

    public function __construct($serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function collection()
    {
        return collect([[
            $this->serviceProvider->id,
            $this->serviceProvider->name,
            $this->serviceProvider->email,
            $this->serviceProvider->type,
            $this->serviceProvider->status,
            $this->serviceProvider->rating,
            $this->serviceProvider->created_at->format('Y-m-d'),
            $this->serviceProvider->phone,
            $this->serviceProvider->address,
            $this->serviceProvider->description,
            $this->serviceProvider->website,
        ]]);
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام',
            'ایمیل',
            'نوع',
            'وضعیت',
            'امتیاز',
            'تاریخ ثبت‌نام',
            'تلفن',
            'آدرس',
            'توضیحات',
            'وب‌سایت'
        ];
    }

    public function title(): string
    {
        return 'مشخصات خدمات‌دهنده';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:K1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EEEEEE']]],
        ];
    }
}

class ServiceProviderActivitiesSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $serviceProvider;

    public function __construct($serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function collection()
    {
        return $this->serviceProvider->activities->map(function ($activity) {
            return [
                $activity->id,
                $activity->activity_name,
                $activity->is_active ? 'فعال' : 'غیرفعال'
            ];
        });
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام فعالیت',
            'وضعیت'
        ];
    }

    public function title(): string
    {
        return 'فعالیت‌ها';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:C1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EEEEEE']]],
        ];
    }
}

class ServiceProviderOrdersSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $serviceProvider;

    public function __construct($serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function collection()
    {
        return $this->serviceProvider->orders->map(function ($order) {
            return [
                $order->order_id,
                $order->user ? $order->user->name : 'کاربر ناشناس',
                $order->created_at->format('Y-m-d'),
                $order->status,
                $order->total_amount,
                $order->payment_status
            ];
        });
    }

    public function headings(): array
    {
        return [
            'شناسه سفارش',
            'نام مشتری',
            'تاریخ سفارش',
            'وضعیت',
            'مبلغ کل',
            'وضعیت پرداخت'
        ];
    }

    public function title(): string
    {
        return 'سفارشات';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:F1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EEEEEE']]],
        ];
    }
}

class ServiceProviderChartDataSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $activityChart;

    public function __construct($activityChart)
    {
        $this->activityChart = $activityChart;
    }

    public function collection()
    {
        $rows = [];
        foreach ($this->activityChart['labels'] as $index => $month) {
            $rows[] = [
                $month,
                $this->activityChart['data'][$index]
            ];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'ماه',
            'تعداد سفارشات تکمیل شده'
        ];
    }

    public function title(): string
    {
        return 'نمودار فعالیت';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:B1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EEEEEE']]],
        ];
    }
}
