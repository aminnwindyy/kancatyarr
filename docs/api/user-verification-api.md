# راهنمای API تأیید اطلاعات کاربر و مدیریت سفارشات

این مستندات شامل اطلاعات مورد نیاز برای استفاده از API تأیید اطلاعات کاربر و مدیریت سفارشات است.

## احراز هویت

تمامی درخواست‌های زیر نیاز به توکن احراز هویت دارند. توکن باید در هدر HTTP به صورت زیر ارسال شود:

```
Authorization: Bearer {YOUR_TOKEN}
```

## API مربوط به اطلاعات کاربر

### دریافت اطلاعات کاربر فعلی

```
GET /api/v1/user/profile
```

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "first_name": "نام",
    "last_name": "نام خانوادگی",
    "email": "example@example.com",
    "phone_number": "09123456789",
    "is_phone_verified": false,
    "national_id": "1234567890",
    "is_national_id_verified": false,
    "sheba_number": "IR123456789012345678901234",
    "is_sheba_verified": false,
    "is_first_name_verified": true,
    "is_last_name_verified": true
    // ... سایر اطلاعات
  }
}
```

### به‌روزرسانی اطلاعات کاربر

```
POST /api/v1/user/profile
```

#### پارامترهای درخواست:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| first_name | string | نام |
| last_name | string | نام خانوادگی |
| phone_number | string | شماره تلفن (فرمت: 09XXXXXXXXX) |
| national_id | string | کد ملی (10 رقم) |
| sheba_number | string | شماره شبا (24 کاراکتر) |

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "message": "اطلاعات کاربری با موفقیت به‌روزرسانی شد",
  "data": {
    // اطلاعات به‌روزرسانی شده کاربر
  }
}
```

### تأیید اطلاعات کاربر (فقط ادمین)

```
POST /api/v1/admin/users/{userId}/verify
```

#### پارامترهای درخواست:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| verify_first_name | boolean | تأیید نام |
| verify_last_name | boolean | تأیید نام خانوادگی |
| verify_phone | boolean | تأیید شماره تلفن |
| verify_national_id | boolean | تأیید کد ملی |
| verify_sheba | boolean | تأیید شماره شبا |

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "message": "اطلاعات کاربر با موفقیت تایید شد",
  "data": {
    // اطلاعات به‌روزرسانی شده کاربر
  }
}
```

### دریافت لیست کاربران (فقط ادمین)

```
GET /api/v1/admin/users
```

#### پارامترهای Query:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| per_page | integer | تعداد آیتم‌ها در هر صفحه (پیش‌فرض: 15) |
| search | string | جستجو در نام، نام خانوادگی، ایمیل و شماره تلفن |

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "user_id": 1,
        "first_name": "نام",
        "last_name": "نام خانوادگی",
        // ...
      },
      // ...
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 5,
    "last_page_url": "...",
    "links": [
      // ...
    ],
    "next_page_url": "...",
    "path": "...",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 68
  }
}
```

### دریافت اطلاعات یک کاربر خاص (فقط ادمین)

```
GET /api/v1/admin/users/{userId}
```

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "first_name": "نام",
    "last_name": "نام خانوادگی",
    // ...
  }
}
```

## API مربوط به سفارشات

### دریافت لیست سفارشات کاربر

```
GET /api/v1/user/orders
```

#### پارامترهای Query:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| per_page | integer | تعداد آیتم‌ها در هر صفحه (پیش‌فرض: 10) |
| status | string | فیلتر بر اساس وضعیت سفارش |

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "order_id": 1,
        "user_id": 1,
        "status": "pending",
        "total_amount": "1500000.00",
        "created_at": "2025-05-10T12:00:00.000000Z",
        "seller": {
          "seller_id": 1,
          "shop_name": "فروشگاه نمونه"
        }
        // ...
      },
      // ...
    ],
    // اطلاعات پیجینیشن
  }
}
```

### دریافت جزئیات یک سفارش

```
GET /api/v1/user/orders/{orderId}
```

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "data": {
    "order_id": 1,
    "user_id": 1,
    "seller_id": 1,
    "status": "pending",
    "total_amount": "1500000.00",
    "payment_status": "paid",
    "payment_method": "online",
    "shipping_address": "...",
    "shipping_city": "تهران",
    "shipping_province": "تهران",
    "shipping_postal_code": "1234567890",
    "shipping_phone": "09123456789",
    "shipping_cost": "50000.00",
    "tax_amount": "90000.00",
    "discount_amount": "0.00",
    "notes": null,
    "created_at": "2025-05-10T12:00:00.000000Z",
    "updated_at": "2025-05-10T12:00:00.000000Z",
    "items": [
      {
        "order_item_id": 1,
        "order_id": 1,
        "product_id": 1,
        "quantity": 2,
        "price": "750000.00",
        "product": {
          "product_id": 1,
          "name": "محصول نمونه",
          "image": "products/1.jpg"
        }
      }
    ],
    "seller": {
      "seller_id": 1,
      "user_id": 2,
      "shop_name": "فروشگاه نمونه"
    },
    "status_history": [
      {
        "id": 1,
        "order_id": 1,
        "status": "pending",
        "notes": "سفارش ثبت شد",
        "created_by": 1,
        "created_at": "2025-05-10T12:00:00.000000Z"
      }
    ],
    "shipping": {
      "id": 1,
      "order_id": 1,
      "tracking_code": "12345678",
      "carrier": "پست",
      "estimated_delivery_date": "2025-05-15",
      "actual_delivery_date": null
    }
  }
}
```

### دریافت لیست تمام سفارشات (فقط ادمین)

```
GET /api/v1/admin/orders
```

#### پارامترهای Query:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| per_page | integer | تعداد آیتم‌ها در هر صفحه (پیش‌فرض: 15) |
| status | string | فیلتر بر اساس وضعیت سفارش |
| user_id | integer | فیلتر بر اساس شناسه کاربر |
| seller_id | integer | فیلتر بر اساس شناسه فروشنده |
| search | string | جستجو در شناسه سفارش، آدرس و شماره تلفن |

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "data": {
    // مشابه پاسخ دریافت لیست سفارشات کاربر، اما شامل اطلاعات بیشتر
  }
}
```

### به‌روزرسانی وضعیت سفارش (فقط ادمین)

```
POST /api/v1/admin/orders/{orderId}/status
```

#### پارامترهای درخواست:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| status | string | وضعیت جدید (pending, processing, shipped, delivered, cancelled) |
| notes | string | توضیحات (اختیاری) |

#### پاسخ موفقیت‌آمیز:

```json
{
  "success": true,
  "message": "وضعیت سفارش با موفقیت به‌روزرسانی شد",
  "data": {
    // اطلاعات به‌روزرسانی شده سفارش
  }
}
```

## کدهای خطا

| کد HTTP | توضیحات |
|---------|---------|
| 401 | عدم احراز هویت |
| 403 | عدم دسترسی |
| 404 | منبع مورد نظر یافت نشد |
| 422 | اطلاعات ارسالی نامعتبر است |
| 500 | خطای سرور | 
