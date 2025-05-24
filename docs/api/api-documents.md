# مستندات API مدیریت مدارک خدمات‌دهندگان

## دریافت لیست مدارک یک خدمات‌دهنده 

نقطه اتصال: `GET /api/service-providers/{id}/documents`

پارامترها:
- `id`: شناسه خدمات‌دهنده

نمونه پاسخ:
```json
{
  "success": true,
  "data": {
    "service_provider": {
      "id": 1,
      "name": "نام خدمات‌دهنده",
      "email": "email@example.com",
      "phone": "09123456789",
      "national_code": "1234567890",
      "business_license": "12345",
      "status": "pending"
    },
    "documents": [
      {
        "id": 1,
        "document_type": "national_card",
        "file_path": "service_provider_documents/1/12345.jpg",
        "status": "pending",
        "description": "",
        "created_at": "2025-05-15 12:30:45",
        "updated_at": "2025-05-15 12:30:45",
        "download_url": "http://example.com/api/service-providers/1/documents/1/download"
      }
    ]
  }
}
```

## تایید یا رد مدرک

نقطه اتصال: `PUT /api/service-providers/{id}/documents/{documentId}/status`

پارامترها:
- `id`: شناسه خدمات‌دهنده
- `documentId`: شناسه مدرک

بدنه درخواست:
```json
{
  "status": "approved", // یا "rejected"
  "description": "توضیحات درباره دلیل رد مدرک"
}
```

نمونه پاسخ:
```json
{
  "success": true,
  "message": "مدرک با موفقیت تایید شد."
}
```

## دانلود مدرک

نقطه اتصال: `GET /api/service-providers/{id}/documents/{documentId}/download`

پارامترها:
- `id`: شناسه خدمات‌دهنده
- `documentId`: شناسه مدرک

پاسخ: فایل مدرک (PDF, JPG, PNG)

## فیلتر خدمات‌دهندگان بر اساس وضعیت

نقطه اتصال: `GET /api/service-providers/documents/filter`

پارامترهای کوئری:
- `filter`: وضعیت (pending, approved, rejected) - پیش‌فرض: pending
- `page`: شماره صفحه - پیش‌فرض: 1
- `limit`: تعداد آیتم در صفحه - پیش‌فرض: 10
- `search`: عبارت جستجو در نام، ایمیل، تلفن یا کد ملی

نمونه پاسخ:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "نام خدمات‌دهنده",
      "email": "email@example.com",
      "phone": "09123456789",
      "national_code": "1234567890",
      "business_license": "12345",
      "status": "pending",
      "approved_documents_count": 1,
      "rejected_documents_count": 0
    }
  ],
  "total_pages": 5,
  "current_page": 1,
  "total": 50
}
```

## دانلود گزارش مدارک

نقطه اتصال: `GET /api/service-providers/documents/export`

پارامترهای کوئری:
- `status`: فیلتر وضعیت (pending, approved, rejected)
- `from_date`: تاریخ شروع (YYYY-MM-DD)
- `to_date`: تاریخ پایان (YYYY-MM-DD)

نمونه پاسخ:
```json
{
  "success": true,
  "message": "گزارش با موفقیت تولید شد.",
  "download_url": "http://example.com/storage/exports/documents-report-2025-05-15.xlsx"
}
```

## آپلود مدرک جدید

نقطه اتصال: `POST /api/service-providers/{id}/documents/upload`

پارامترها:
- `id`: شناسه خدمات‌دهنده
- Form-data:
  - `document_type`: نوع مدرک (national_card, business_license, photo)
  - `file`: فایل مدرک (JPG, PNG, PDF، حداکثر 5MB)

نمونه پاسخ:
```json
{
  "success": true,
  "message": "مدرک با موفقیت آپلود شد.",
  "document": {
    "id": 1,
    "document_type": "national_card",
    "file_path": "service_provider_documents/1/12345.jpg",
    "status": "pending",
    "created_at": "2025-05-15 12:30:45"
  }
}
```
```

## 8. نکات پیاده‌سازی

1. این API ها با بهینه‌سازی کوئری‌ها (استفاده از JOIN) برای کاهش تعداد درخواست‌ها به دیتابیس پیاده‌سازی شده‌اند.
2. سیستم اعلان‌رسانی برای اطلاع به خدمات‌دهندگان در صورت رد مدارک پیاده‌سازی شده است.
3. مدیریت فایل‌ها با استفاده از سیستم فایل Laravel (Storage) انجام می‌شود.
4. برای امنیت، از اعتبارسنجی ورودی‌ها و بررسی دسترسی ادمین استفاده شده است.
5. پیاده‌سازی صفحه‌بندی برای نمایش لیست‌ها انجام شده است.
6. امکان گزارش‌گیری از مدارک با فیلترهای مختلف فراهم شده است.

برای استفاده از این کدها، باید پکیج‌های مورد نیاز را نصب کنید:
```bash
composer require maatwebsite/excel
```

همچنین نیاز است که مدل‌های ServiceProvider و ServiceProviderDocument را به‌روز کنید تا روابط و فیلدهای مورد نیاز را داشته باشند.