# مستندات API مدیریت محصولات خدمات‌دهندگان

## دریافت لیست محصولات

نقطه اتصال: `GET /api/products`

پارامترهای درخواست:
- `limit`: تعداد آیتم در هر صفحه (پیش‌فرض: 10)
- `page`: شماره صفحه
- `search`: جستجو بر اساس نام یا توضیحات
- `service_provider_id`: فیلتر بر اساس خدمات‌دهنده
- `category_id`: فیلتر بر اساس دسته‌بندی
- `approval_status`: فیلتر بر اساس وضعیت تایید (approved, rejected, pending)
- `sort_field`: فیلد مرتب‌سازی (پیش‌فرض: created_at)
- `sort_direction`: جهت مرتب‌سازی (asc, desc) - پیش‌فرض: desc

نمونه پاسخ:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "product_id": 1,
        "category_id": 5,
        "seller_id": null,
        "service_provider_id": 3,
        "name": "محصول نمونه",
        "description": "توضیحات محصول",
        "price": "250000.00",
        "image_url": "/storage/product_images/3/1683789456_product-image.jpg",
        "stock": 50,
        "approval_status": "approved",
        "approval_reason": null,
        "created_at": "2025-05-10T08:24:16.000000Z",
        "updated_at": "2025-05-10T08:24:16.000000Z",
        "category": {
          "category_id": 5,
          "name": "لوازم الکترونیکی",
          "description": "انواع لوازم الکترونیکی"
        },
        "service_provider": {
          "id": 3,
          "name": "شرکت فنی مهندسی نمونه",
          "email": "info@example.com"
        }
      }
    ],
    "from": 1,
    "last_page": 5,
    "per_page": 10,
    "to": 10,
    "total": 50
  }
}
```

## دریافت جزئیات محصول

نقطه اتصال: `GET /api/products/{id}`

پارامترها:
- `id`: شناسه محصول

نمونه پاسخ:
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "category_id": 5,
    "seller_id": null,
    "service_provider_id": 3,
    "name": "محصول نمونه",
    "description": "توضیحات محصول",
    "price": "250000.00",
    "image_url": "/storage/product_images/3/1683789456_product-image.jpg",
    "stock": 50,
    "approval_status": "approved",
    "approval_reason": null,
    "created_at": "2025-05-10T08:24:16.000000Z",
    "updated_at": "2025-05-10T08:24:16.000000Z",
    "category": {
      "category_id": 5,
      "name": "لوازم الکترونیکی",
      "description": "انواع لوازم الکترونیکی"
    },
    "service_provider": {
      "id": 3,
      "name": "شرکت فنی مهندسی نمونه",
      "email": "info@example.com"
    },
    "reviews": [
      {
        "id": 12,
        "user_id": 5,
        "product_id": 1,
        "rating": 4,
        "comment": "کیفیت خوبی داشت"
      }
    ]
  }
}
```

## ایجاد محصول جدید

نقطه اتصال: `POST /api/products`

پارامترها:
- `service_provider_id`: شناسه خدمات‌دهنده (الزامی)
- `category_id`: شناسه دسته‌بندی (الزامی)
- `name`: نام محصول (الزامی)
- `description`: توضیحات محصول (الزامی)
- `price`: قیمت محصول (الزامی)
- `stock`: موجودی محصول (الزامی)
- `image`: تصویر محصول (اختیاری - فایل تصویر)

نمونه پاسخ:
```json
{
  "success": true,
  "message": "محصول با موفقیت ایجاد شد و در انتظار تایید است",
  "data": {
    "product_id": 51,
    "category_id": 5,
    "service_provider_id": 3,
    "name": "محصول جدید",
    "description": "توضیحات محصول جدید",
    "price": "350000.00",
    "image_url": "/storage/product_images/3/1683789856_new-product-image.jpg",
    "stock": 25,
    "approval_status": "pending",
    "created_at": "2025-05-15T08:24:16.000000Z",
    "updated_at": "2025-05-15T08:24:16.000000Z"
  }
}
```

## به‌روزرسانی محصول

نقطه اتصال: `PUT /api/products/{id}`

پارامترها:
- `id`: شناسه محصول
- `category_id`: شناسه دسته‌بندی (اختیاری)
- `name`: نام محصول (اختیاری)
- `description`: توضیحات محصول (اختیاری)
- `price`: قیمت محصول (اختیاری)
- `stock`: موجودی محصول (اختیاری)
- `image`: تصویر محصول (اختیاری - فایل تصویر)

نمونه پاسخ:
```json
{
  "success": true,
  "message": "محصول با موفقیت به‌روزرسانی شد و در انتظار تایید است",
  "data": {
    "product_id": 1,
    "category_id": 5,
    "service_provider_id": 3,
    "name": "محصول به‌روز شده",
    "description": "توضیحات به‌روز شده",
    "price": "275000.00",
    "image_url": "/storage/product_images/3/1683790056_updated-product-image.jpg",
    "stock": 35,
    "approval_status": "pending",
    "created_at": "2025-05-10T08:24:16.000000Z",
    "updated_at": "2025-05-15T09:34:16.000000Z"
  }
}
```

## حذف محصول

نقطه اتصال: `DELETE /api/products/{id}`

پارامترها:
- `id`: شناسه محصول

نمونه پاسخ:
```json
{
  "success": true,
  "message": "محصول با موفقیت حذف شد"
}
```

## تغییر وضعیت تایید محصول (مخصوص ادمین)

نقطه اتصال: `PUT /api/products/{id}/approval`

پارامترها:
- `id`: شناسه محصول
- `approval_status`: وضعیت تایید (approved, rejected, pending)
- `approval_reason`: دلیل تایید یا رد (اختیاری)

نمونه پاسخ:
```json
{
  "success": true,
  "message": "وضعیت تایید محصول با موفقیت به‌روزرسانی شد",
  "data": {
    "product_id": 1,
    "approval_status": "approved",
    "approval_reason": "محصول مطابق با استانداردهای سایت است"
  }
}
```

## دریافت محصولات در انتظار تایید (مخصوص ادمین)

نقطه اتصال: `GET /api/products/pending/list`

پارامترهای درخواست:
- `limit`: تعداد آیتم در هر صفحه (پیش‌فرض: 10)
- `page`: شماره صفحه

نمونه پاسخ:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "product_id": 1,
        "category_id": 5,
        "service_provider_id": 3,
        "name": "محصول نمونه",
        "description": "توضیحات محصول",
        "price": "250000.00",
        "approval_status": "pending",
        "created_at": "2025-05-10T08:24:16.000000Z",
        "updated_at": "2025-05-10T08:24:16.000000Z",
        "category": {
          "category_id": 5,
          "name": "لوازم الکترونیکی"
        },
        "service_provider": {
          "id": 3,
          "name": "شرکت فنی مهندسی نمونه"
        }
      }
    ],
    "from": 1,
    "last_page": 3,
    "per_page": 10,
    "to": 10,
    "total": 25
  }
}
```

## دریافت محصولات یک خدمات‌دهنده خاص

نقطه اتصال: `GET /api/service-providers/{serviceProviderId}/products`

پارامترها:
- `serviceProviderId`: شناسه خدمات‌دهنده
- `limit`: تعداد آیتم در هر صفحه (پیش‌فرض: 10)
- `page`: شماره صفحه
- `approval_status`: فیلتر بر اساس وضعیت تایید (اختیاری)
- `sort_field`: فیلد مرتب‌سازی (پیش‌فرض: created_at)
- `sort_direction`: جهت مرتب‌سازی (asc, desc) - پیش‌فرض: desc

نمونه پاسخ:
```json
{
  "success": true,
  "service_provider": {
    "id": 3,
    "name": "شرکت فنی مهندسی نمونه"
  },
  "data": {
    "current_page": 1,
    "data": [
      {
        "product_id": 1,
        "category_id": 5,
        "service_provider_id": 3,
        "name": "محصول نمونه",
        "description": "توضیحات محصول",
        "price": "250000.00",
        "image_url": "/storage/product_images/3/1683789456_product-image.jpg",
        "stock": 50,
        "approval_status": "approved",
        "created_at": "2025-05-10T08:24:16.000000Z",
        "updated_at": "2025-05-10T08:24:16.000000Z",
        "category": {
          "category_id": 5,
          "name": "لوازم الکترونیکی"
        }
      }
    ],
    "from": 1,
    "last_page": 2,
    "per_page": 10,
    "to": 10,
    "total": 15
  }
}
```
```

با پیاده‌سازی این فایل‌ها، سیستم مدیریت محصولات برای خدمات‌دهندگان به طور کامل پیاده‌سازی می‌شود. این سیستم شامل مسیرهای API، کلاس‌های اعتبارسنجی و نوتیفیکیشن، و مستندات API می‌باشد.