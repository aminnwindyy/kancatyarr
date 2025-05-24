<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;
    
    protected $filter;
    protected $deliveryType;
    protected $status;
    protected $fromDate;
    protected $toDate;
    protected $categoryId;
    protected $providerType;

    /**
     * @param string|null $filter
     * @param string|null $deliveryType
     * @param string|null $status
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param int|null $categoryId
     * @param string|null $providerType
     */
    public function __construct($filter = null, $deliveryType = null, $status = null, $fromDate = null, $toDate = null, $categoryId = null, $providerType = null)
    {
        $this->filter = $filter;
        $this->deliveryType = $deliveryType;
        $this->status = $status;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->categoryId = $categoryId;
        $this->providerType = $providerType;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = Order::query()->with(['user', 'serviceProvider', 'category']);

        // اعمال فیلترها
        if ($this->filter && $this->filter !== 'all') {
            $query->ofType($this->filter);
        }

        if ($this->deliveryType) {
            $query->ofDeliveryType($this->deliveryType);
        }

        if ($this->status) {
            $query->ofStatus($this->status);
        }
        
        if ($this->categoryId) {
            $query->ofCategory($this->categoryId);
        }
        
        if ($this->providerType) {
            $query->ofProviderType($this->providerType);
        }

        // فیلتر تاریخ
        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->orderBy('created_at', 'desc');
    }
    
    public function headings(): array
    {
        return [
            'شناسه',
            'نام کاربر',
            'نام خدمات‌دهنده',
            'نوع خدمات‌دهنده',
            'دسته‌بندی',
            'نوع سفارش',
            'نوع ارسال',
            'وضعیت',
            'توضیحات',
            'تاریخ ایجاد',
        ];
    }
    
    public function map($order): array
    {
        $statusMap = [
            'pending' => 'در انتظار',
            'accepted' => 'تایید شده',
            'rejected' => 'رد شده',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
        ];
        
        $orderTypeMap = [
            'public' => 'عمومی',
            'private' => 'اختصاصی',
        ];
        
        $deliveryTypeMap = [
            'national' => 'سراسری',
            'local' => 'شهری',
        ];
        
        $providerTypeMap = [
            'business' => 'صنفی',
            'connectyar' => 'کانکت‌یار',
        ];

        return [
            $order->order_id,
            $order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'نامشخص',
            $order->serviceProvider ? $order->serviceProvider->name : 'نامشخص',
            isset($providerTypeMap[$order->service_provider_type]) ? $providerTypeMap[$order->service_provider_type] : 'نامشخص',
            $order->category ? $order->category->name : 'نامشخص',
            $orderTypeMap[$order->order_type] ?? $order->order_type,
            $deliveryTypeMap[$order->delivery_type] ?? $order->delivery_type,
            $statusMap[$order->status] ?? $order->status,
            $order->description,
            $order->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
