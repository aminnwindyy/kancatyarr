<?php

namespace App\Exports;

use App\Models\ProductRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductRequestsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    
    protected $status;
    protected $period;

    public function __construct($status = null, $period = null)
    {
        $this->status = $status;
        $this->period = $period;
    }

    public function query()
    {
        $query = ProductRequest::query()->with([
            'serviceProvider:id,name',
            'category:id,name'
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
            'نام محصول',
            'دسته‌بندی',
            'قیمت',
            'وضعیت',
            'تاریخ درخواست'
        ];
    }
    
    public function map($row): array
    {
        return [
            $row->id,
            $row->serviceProvider->name ?? 'نامشخص',
            $row->name,
            $row->category->name ?? 'نامشخص',
            $row->price,
            $row->status,
            $row->created_at->format('Y-m-d')
        ];
    }
    
    private function translateStatus($status): string
    {
        $statuses = [
            'pending' => 'در انتظار',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
        ];
        
        return $statuses[$status] ?? $status;
    }
}
