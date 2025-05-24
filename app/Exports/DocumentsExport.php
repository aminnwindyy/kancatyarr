<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $documents;

    public function __construct($documents)
    {
        $this->documents = $documents;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->documents;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'شناسه',
            'نام خدمات‌دهنده',
            'ایمیل خدمات‌دهنده',
            'شماره تماس',
            'نوع مدرک',
            'وضعیت',
            'توضیحات',
            'تاریخ بارگذاری',
            'تاریخ بروزرسانی'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($document): array
    {
        $documentType = [
            'national_card' => 'کارت ملی',
            'business_license' => 'جواز کسب',
            'photo' => 'عکس پرسنلی',
        ][$document->document_type] ?? $document->document_type;

        $status = [
            'pending' => 'در انتظار بررسی',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
        ][$document->status] ?? $document->status;

        return [
            $document->id,
            $document->provider_name,
            $document->provider_email,
            $document->provider_phone,
            $documentType,
            $status,
            $document->description,
            $document->created_at,
            $document->updated_at,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
