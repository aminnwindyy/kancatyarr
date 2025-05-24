# مستندات API های مدیریت کاربران

در این مستند، API های مربوط به مدیریت کاربران در پنل ادمین توضیح داده شده است.

## 1. دریافت آمار کاربران

- **URL**: `/api/admin/users/stats`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.view`
- **پاسخ موفق**:
  ```json
  {
    "total": 1250,
    "active": 980,
    "inactive": 270,
    "with_subscription": 540,
    "without_subscription": 710,
    "expired_subscription": 120,
    "registered_today": 15
  }
  ```

## 2. دریافت لیست کاربران با فیلتر

- **URL**: `/api/admin/users?filter=active&search=محمد`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.view`
- **پارامترهای Query String**:
  - `filter`: نوع فیلتر (مقادیر: `active`, `inactive`, `with_subscription`, `without_subscription`, `expired_subscription`, `registered_today`)
  - `search`: کلمه جستجو در نام، نام خانوادگی، ایمیل یا شماره تلفن
  - `page`: شماره صفحه (پیش‌فرض: 1)
- **پاسخ موفق**:
  ```json
  {
    "current_page": 1,
    "data": [
      {
        "user_id": 123,
        "first_name": "محمد",
        "last_name": "کریمی",
        "email": "mohammad@example.com",
        "phone": "09123456789",
        "is_active": true,
        "created_at": "2024-11-05T10:30:45",
        "profile_image": "http://example.com/storage/profile_images/user123.jpg"
      },
      ...
    ],
    "total": 15,
    "per_page": 15,
    "last_page": 1,
    ...
  }
  ```

## 3. مشاهده جزئیات کاربر

- **URL**: `/api/admin/users/123`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.view`
- **پاسخ موفق**:
  ```json
  {
    "user": {
      "user_id": 123,
      "first_name": "محمد",
      "last_name": "کریمی",
      "email": "mohammad@example.com",
      "phone": "09123456789",
      "is_active": true,
      "created_at": "2024-11-05T10:30:45",
      "profile_image": "http://example.com/storage/profile_images/user123.jpg",
      "subscriptions": [
        {
          "subscription_id": 45,
          "plan_name": "طلایی",
          "start_date": "2024-10-05",
          "end_date": "2025-10-05"
        }
      ],
      "orders": [
        {
          "order_id": 345,
          "total_amount": "580000.00",
          "status": "completed",
          "created_at": "2024-11-01T14:30:22"
        }
      ],
      "wallet": {
        "wallet_id": 123,
        "balance": "250000.00"
      }
    }
  }
  ```

## 4. افزودن کاربر جدید

- **URL**: `/api/admin/users`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.create`
- **Body**: `FormData` شامل:
  ```json
  {
    "first_name": "علی",
    "last_name": "محمدی",
    "email": "ali@example.com",
    "phone": "09123456789",
    "password": "SecurePassword123",
    "is_active": true,
    "profile_image": [فایل تصویر]
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "کاربر با موفقیت ایجاد شد",
    "user": {
      "user_id": 124,
      "first_name": "علی",
      "last_name": "محمدی",
      "email": "ali@example.com",
      "phone": "09123456789",
      "is_active": true,
      "created_at": "2024-11-10T09:45:30",
      "profile_image": "profile_images/abc123.jpg"
    }
  }
  ```

## 5. ویرایش کاربر

- **URL**: `/api/admin/users/124`
- **Method**: `PUT`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.edit`
- **Body**: `FormData` شامل:
  ```json
  {
    "first_name": "علی‌رضا",
    "is_active": false
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "کاربر با موفقیت بروزرسانی شد",
    "user": {
      "user_id": 124,
      "first_name": "علی‌رضا",
      "last_name": "محمدی",
      "email": "ali@example.com",
      "phone": "09123456789",
      "is_active": false,
      "created_at": "2024-11-10T09:45:30",
      "profile_image": "profile_images/abc123.jpg"
    }
  }
  ```

## 6. حذف کاربر

- **URL**: `/api/admin/users/124`
- **Method**: `DELETE`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.delete`
- **پاسخ موفق**:
  ```json
  {
    "message": "کاربر با موفقیت حذف شد"
  }
  ```

## 7. ارسال پیام به کاربر

- **URL**: `/api/admin/users/123/message`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.message`
- **Body**:
  ```json
  {
    "subject": "اطلاعیه مهم",
    "content": "با سلام و احترام، به اطلاع می‌رساند که حساب کاربری شما ارتقا یافت."
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "پیام با موفقیت ارسال شد",
    "sent_message": {
      "message_id": 456,
      "sender_id": 1,
      "sender_type": "admin",
      "recipient_id": 123,
      "recipient_type": "user",
      "subject": "اطلاعیه مهم",
      "content": "با سلام و احترام، به اطلاع می‌رساند که حساب کاربری شما ارتقا یافت.",
      "is_read": false,
      "created_at": "2024-11-10T11:23:45"
    }
  }
  ```

## 8. دریافت خروجی اکسل از اطلاعات کاربران

- **URL**: `/api/admin/users/export?filter=active&search=محمد`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.export`
- **پارامترهای Query String**:
  - `filter`: نوع فیلتر (مقادیر: `active`, `inactive`, `with_subscription`, `without_subscription`, `expired_subscription`, `registered_today`)
  - `search`: کلمه جستجو در نام، نام خانوادگی، ایمیل یا شماره تلفن
- **پاسخ موفق**: فایل اکسل با اطلاعات کاربران

## 9. مدیریت اشتراک کاربر (تمدید یا تغییر)

- **URL**: `/api/admin/users/123/subscription`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.edit`
- **Body**:
  ```json
  {
    "subscription_id": 45,  // اختیاری - در صورت ارسال، اشتراک موجود ویرایش می‌شود
    "plan_id": 2,  // الزامی - شناسه طرح اشتراک جدید
    "start_date": "2024-11-15 10:00:00",  // الزامی - تاریخ و زمان شروع
    "end_date": "2025-11-15 10:00:00",  // الزامی - تاریخ و زمان پایان
    "notes": "تمدید با تخفیف ویژه",  // اختیاری - یادداشت‌های مربوط به اشتراک
    "is_custom_schedule": true,  // اختیاری - آیا زمان‌بندی سفارشی دارد؟
    "schedule_details": {  // اختیاری - در صورت فعال بودن زمان‌بندی سفارشی الزامی است
      "weekdays": [1, 2, 3, 4, 5],  // روزهای هفته (0=یکشنبه، 6=شنبه)
      "hours": [
        {"start": "08:00", "end": "12:00"},
        {"start": "14:00", "end": "18:00"}
      ],
      "excluded_dates": ["2024-12-25", "2025-01-01"]  // تاریخ‌های استثنا
    }
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "اشتراک کاربر با موفقیت تغییر شد",
    "subscription": {
      "subscription_id": 45,
      "user_id": 123,
      "plan_id": 2,
      "start_date": "2024-11-15 10:00:00",
      "end_date": "2025-11-15 10:00:00",
      "notes": "تمدید با تخفیف ویژه",
      "is_custom_schedule": true,
      "schedule_details": {
        "weekdays": [1, 2, 3, 4, 5],
        "hours": [
          {"start": "08:00", "end": "12:00"},
          {"start": "14:00", "end": "18:00"}
        ],
        "excluded_dates": ["2024-12-25", "2025-01-01"]
      },
      "created_at": "2024-10-05T10:30:45",
      "updated_at": "2024-11-10T14:23:11"
    }
  }
  ```

## 10. دریافت جزئیات سفارش کاربر

- **URL**: `/api/admin/users/123/orders/456`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.view`
- **پاسخ موفق**:
  ```json
  {
    "order": {
      "order_id": 456,
      "user_id": 123,
      "total_amount": "780000.00",
      "status": "completed",
      "created_at": "2024-10-25T16:45:30",
      "updated_at": "2024-10-26T09:15:22",
      "items": [
        {
          "item_id": 789,
          "order_id": 456,
          "product_id": 45,
          "quantity": 2,
          "unit_price": "340000.00",
          "total_price": "680000.00",
          "product": {
            "product_id": 45,
            "name": "هدفون بلوتوثی مدل X5",
            "sku": "HB-X5-BLK"
          }
        },
        {
          "item_id": 790,
          "order_id": 456,
          "product_id": 67,
          "quantity": 1,
          "unit_price": "100000.00",
          "total_price": "100000.00",
          "product": {
            "product_id": 67,
            "name": "کابل شارژر Type-C",
            "sku": "CB-TC-100"
          }
        }
      ],
      "transactions": [
        {
          "transaction_id": 567,
          "order_id": 456,
          "amount": "780000.00",
          "status": "completed",
          "payment_method": "online",
          "reference_code": "PAY-12345678",
          "created_at": "2024-10-25T16:50:12"
        }
      ],
      "shipping": {
        "shipping_id": 456,
        "order_id": 456,
        "address": "تهران، خیابان ولیعصر، پلاک 123",
        "tracking_code": "TRK-9876543",
        "shipping_method": "express",
        "shipping_cost": "25000.00",
        "status": "delivered"
      },
      "status_history": [
        {
          "id": 1001,
          "order_id": 456,
          "status": "pending",
          "notes": "سفارش ثبت شد",
          "created_at": "2024-10-25T16:45:30"
        },
        {
          "id": 1002,
          "order_id": 456,
          "status": "processing",
          "notes": "پرداخت با موفقیت انجام شد",
          "created_at": "2024-10-25T16:50:15"
        },
        {
          "id": 1003,
          "order_id": 456,
          "status": "shipped",
          "notes": "سفارش ارسال شد",
          "created_at": "2024-10-26T08:30:00"
        },
        {
          "id": 1004,
          "order_id": 456,
          "status": "completed",
          "notes": "سفارش تحویل داده شد",
          "created_at": "2024-10-26T09:15:22"
        }
      ]
    }
  }
  ```

## 11. دریافت اشتراک‌های کاربر

- **URL**: `/api/admin/users/123/subscriptions`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.view`
- **پاسخ موفق**:
  ```json
  {
    "subscriptions": [
      {
        "subscription_id": 45,
        "user_id": 123,
        "plan_id": 2,
        "start_date": "2024-11-15 10:00:00",
        "end_date": "2025-11-15 10:00:00",
        "notes": "تمدید با تخفیف ویژه",
        "is_custom_schedule": true,
        "schedule_details": {
          "weekdays": [1, 2, 3, 4, 5],
          "hours": [
            {"start": "08:00", "end": "12:00"},
            {"start": "14:00", "end": "18:00"}
          ],
          "excluded_dates": ["2024-12-25", "2025-01-01"]
        },
        "created_at": "2024-10-05T10:30:45",
        "updated_at": "2024-11-10T14:23:11",
        "plan": {
          "plan_id": 2,
          "name": "استاندارد",
          "description": "طرح اشتراک استاندارد با امکانات متوسط",
          "price": "250000",
          "duration_days": 30,
          "features": ["دسترسی به محتوای عمومی", "دسترسی به محتوای ویژه", "دانلود ماهانه ۵۰ فایل"],
          "is_active": true
        }
      },
      {
        "subscription_id": 32,
        "user_id": 123,
        "plan_id": 1,
        "start_date": "2024-08-15 08:00:00",
        "end_date": "2024-09-15 08:00:00",
        "notes": "اشتراک اولیه",
        "is_custom_schedule": false,
        "schedule_details": null,
        "created_at": "2024-08-15T08:00:00",
        "updated_at": "2024-08-15T08:00:00",
        "plan": {
          "plan_id": 1,
          "name": "پایه",
          "description": "طرح اشتراک پایه با امکانات محدود",
          "price": "100000",
          "duration_days": 30,
          "features": ["دسترسی به محتوای عمومی", "دانلود ماهانه ۱۰ فایل"],
          "is_active": true
        }
      }
    ],
    "active_subscription": {
      "subscription_id": 45,
      "user_id": 123,
      "plan_id": 2,
      "start_date": "2024-11-15 10:00:00",
      "end_date": "2025-11-15 10:00:00",
      "notes": "تمدید با تخفیف ویژه",
      "is_custom_schedule": true,
      "schedule_details": {
        "weekdays": [1, 2, 3, 4, 5],
        "hours": [
          {"start": "08:00", "end": "12:00"},
          {"start": "14:00", "end": "18:00"}
        ],
        "excluded_dates": ["2024-12-25", "2025-01-01"]
      },
      "created_at": "2024-10-05T10:30:45",
      "updated_at": "2024-11-10T14:23:11",
      "plan": {
        "plan_id": 2,
        "name": "استاندارد",
        "description": "طرح اشتراک استاندارد با امکانات متوسط",
        "price": "250000",
        "duration_days": 30,
        "features": ["دسترسی به محتوای عمومی", "دسترسی به محتوای ویژه", "دانلود ماهانه ۵۰ فایل"],
        "is_active": true
      }
    },
    "has_active_subscription": true
  }
  ```

## 12. دریافت لیست طرح‌های اشتراک

- **URL**: `/api/admin/subscription-plans`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `users.view`
- **پاسخ موفق**:
  ```json
  {
    "plans": [
      {
        "plan_id": 1,
        "name": "پایه",
        "description": "طرح اشتراک پایه با امکانات محدود",
        "price": "100000",
        "duration_days": 30,
        "features": ["دسترسی به محتوای عمومی", "دانلود ماهانه ۱۰ فایل"],
        "is_active": true,
        "created_at": "2024-10-01T12:00:00",
        "updated_at": "2024-10-01T12:00:00"
      },
      {
        "plan_id": 2,
        "name": "استاندارد",
        "description": "طرح اشتراک استاندارد با امکانات متوسط",
        "price": "250000",
        "duration_days": 30,
        "features": ["دسترسی به محتوای عمومی", "دسترسی به محتوای ویژه", "دانلود ماهانه ۵۰ فایل"],
        "is_active": true,
        "created_at": "2024-10-01T12:00:00",
        "updated_at": "2024-10-01T12:00:00"
      },
      {
        "plan_id": 3,
        "name": "طلایی",
        "description": "طرح اشتراک طلایی با امکانات نامحدود",
        "price": "500000",
        "duration_days": 30,
        "features": ["دسترسی به همه محتوا", "دانلود نامحدود فایل", "پشتیبانی اختصاصی"],
        "is_active": true,
        "created_at": "2024-10-01T12:00:00",
        "updated_at": "2024-10-01T12:00:00"
      }
    ]
  }
  ```

## نکات فنی

1. **مقادیر فیلتر**:
   - `active`: کاربران فعال
   - `inactive`: کاربران غیرفعال
   - `with_subscription`: کاربران دارای اشتراک فعال
   - `without_subscription`: کاربران فاقد اشتراک
   - `expired_subscription`: کاربران با اشتراک منقضی شده
   - `registered_today`: کاربران ثبت‌نام شده امروز

2. **فیلدهای جستجو**:
   - نام (first_name)
   - نام خانوادگی (last_name)
   - ایمیل (email)
   - شماره تلفن (phone)

3. **سطوح دسترسی**:
   - `users.view`: مشاهده لیست و جزئیات کاربران
   - `users.create`: ایجاد کاربر جدید
   - `users.edit`: ویرایش اطلاعات کاربر
   - `users.delete`: حذف کاربر
   - `users.message`: ارسال پیام به کاربر
   - `users.export`: دریافت خروجی اکسل 

4. **زمان‌بندی سفارشی اشتراک**:
   - زمان‌بندی سفارشی اشتراک از طریق فیلد `is_custom_schedule` و `schedule_details` قابل تنظیم است.
   - در صورت فعال بودن زمان‌بندی سفارشی، جزئیات آن باید در فیلد `schedule_details` به صورت JSON ارسال شود.
   - ساختار `schedule_details` شامل:
     - `weekdays`: آرایه‌ای از اعداد ۰ تا ۶ که نشان‌دهنده روزهای هفته است (۰=یکشنبه، ۶=شنبه).
     - `hours`: آرایه‌ای از ساعات فعال در روزهای مشخص شده که هر عنصر شامل `start` و `end` است.
     - `excluded_dates`: آرایه‌ای از تاریخ‌هایی که علی‌رغم قرار گرفتن در بازه اشتراک، استثنا محسوب می‌شوند.
