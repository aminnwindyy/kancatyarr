# مستندات API پنل ادمین

این مستندات، API های مورد نیاز برای پنل ادمین را توضیح می‌دهد. این API ها به فرانت‌اند امکان می‌دهند تا با بک‌اند ارتباط برقرار کرده و عملیات مورد نیاز را انجام دهند.

# سیستم احراز هویت کاربران

بخش احراز هویت کاربران امکان ورود و ثبت‌نام با استفاده از روش‌های مختلف را فراهم می‌کند.

## روش‌های احراز هویت

### 1. احراز هویت با OTP موبایل (روش ارجح)

#### 1.1. درخواست کد OTP

- **URL**: `/api/auth/request-otp`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "phone_number": "09123456789"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "کد تایید با موفقیت ارسال شد",
    "expires_at": 1716443789 // timestamp انقضای کد (2 دقیقه)
  }
  ```

#### 1.2. تایید کد OTP و دریافت توکن

- **URL**: `/api/auth/verify-otp`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "phone_number": "09123456789",
    "otp": "1234"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "ورود با موفقیت انجام شد",
    "user": {
      "id": 1,
      "name": "کاربر نمونه",
      "email": "user@example.com",
      "phone_number": "09123456789"
    },
    "token": "your-access-token",
    "is_new_user": false
  }
  ```

### 2. احراز هویت با ایمیل و رمز عبور

#### 2.1. ثبت‌نام کاربر جدید

- **URL**: `/api/auth/register`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "name": "کاربر نمونه",
    "email": "user@example.com",
    "phone_number": "09123456789",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "ثبت‌نام با موفقیت انجام شد",
    "user": {
      "id": 1,
      "name": "کاربر نمونه",
      "email": "user@example.com",
      "phone_number": "09123456789"
    },
    "token": "your-access-token"
  }
  ```

#### 2.2. ورود کاربر

- **URL**: `/api/auth/login`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "ورود با موفقیت انجام شد",
    "user": {
      "id": 1,
      "name": "کاربر نمونه",
      "email": "user@example.com",
      "phone_number": "09123456789"
    },
    "token": "your-access-token"
  }
  ```

### 3. مدیریت حساب کاربری

#### 3.1. دریافت اطلاعات پروفایل

- **URL**: `/api/user/profile`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "data": {
      "id": 1,
      "name": "کاربر نمونه",
      "email": "user@example.com",
      "phone_number": "09123456789",
      "created_at": "2023-05-07 14:30:45",
      "updated_at": "2023-05-07 14:30:45"
    }
  }
  ```

#### 3.2. به‌روزرسانی پروفایل

- **URL**: `/api/user/profile`
- **Method**: `PUT`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "name": "نام جدید",
    "email": "new-email@example.com",
    "phone_number": "09123456789"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "پروفایل با موفقیت به‌روزرسانی شد",
    "data": {
      "id": 1,
      "name": "نام جدید",
      "email": "new-email@example.com",
      "phone_number": "09123456789",
      "created_at": "2023-05-07 14:30:45",
      "updated_at": "2023-05-07 15:45:23"
    }
  }
  ```

#### 3.3. تغییر رمز عبور

- **URL**: `/api/user/change-password`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "current_password": "password123",
    "password": "new-password123",
    "password_confirmation": "new-password123"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "رمز عبور با موفقیت تغییر یافت"
  }
  ```

#### 3.4. خروج از سیستم

- **URL**: `/api/logout`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": "success",
    "message": "خروج با موفقیت انجام شد"
  }
  ```

## جریان کاربر (User Flow)

### ورود با OTP

1. کاربر روی دکمه «ورود برای اتصال» کلیک می‌کند
2. کاربر شماره موبایل خود را وارد می‌کند
3. فرانت‌اند درخواستی به آدرس `/api/auth/request-otp` ارسال می‌کند
4. کاربر کد یکبار مصرف دریافتی را در فرم وارد می‌کند
5. فرانت‌اند درخواستی به آدرس `/api/auth/verify-otp` ارسال می‌کند
6. در صورت موفقیت، توکن دریافت و در `localStorage` ذخیره می‌شود
7. اگر کاربر جدید باشد (`is_new_user: true`)، اطلاعات تکمیلی از او خواسته می‌شود
8. کاربر به داشبورد هدایت می‌شود

### ورود با ایمیل و رمز عبور

1. کاربر روی دکمه «ورود با ایمیل» کلیک می‌کند
2. کاربر ایمیل و رمز عبور خود را وارد می‌کند
3. فرانت‌اند درخواستی به آدرس `/api/auth/login` ارسال می‌کند
4. در صورت موفقیت، توکن دریافت و در `localStorage` ذخیره می‌شود
5. کاربر به داشبورد هدایت می‌شود

## فایل‌های مرتبط

- فایل Postman Collection: `admin-panel-api.postman_collection.json`
- کنترلرهای API: در پوشه `app/Http/Controllers/Api`

## نحوه احراز هویت

کلیه API ها (به‌جز API لاگین) نیاز به توکن احراز هویت دارند. این توکن را باید در هدر درخواست به صورت زیر ارسال کنید:

```
Authorization: Bearer YOUR_TOKEN
```

توکن با استفاده از API لاگین دریافت می‌شود.

## API های موجود

### 1. احراز هویت

#### 1.1. ورود به سیستم

- **URL**: `/api/login`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "email": "your-email@example.com",
    "password": "your-password"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "user": {
      "id": 1,
      "first_name": "مدیر",
      "last_name": "کل",
      "email": "superadmin@example.com",
      "roles": ["super-admin"],
      "permissions": ["users.view", "users.create", ...]
    },
    "token": "your-access-token"
  }
  ```

#### 1.2. خروج از سیستم

- **URL**: `/api/logout`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "message": "خروج با موفقیت انجام شد."
  }
  ```

#### 1.3. دریافت اطلاعات کاربر

- **URL**: `/api/user`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "user": {
      "id": 1,
      "first_name": "مدیر",
      "last_name": "کل",
      "email": "superadmin@example.com",
      "roles": ["super-admin"],
      "permissions": ["users.view", "users.create", ...]
    }
  }
  ```

### 2. داشبورد

#### 2.1. دریافت اطلاعات پایه داشبورد

- **URL**: `/api/dashboard/info`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "date": {
      "gregorian": "2025-05-07",
      "jalali": "1404/02/18"
    },
    "time": "14:30:45",
    "timezone": "Asia/Tehran",
    "app_name": "پنل مدیریت",
    "app_version": "1.0.0"
  }
  ```

#### 2.2. دریافت آمار کلی

- **URL**: `/api/dashboard/stats`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "stats": {
      "users": {
        "count": 120,
        "change_percent": 5.2,
        "is_positive": true
      },
      "sellers": {
        "count": 45,
        "change_percent": 10.3,
        "is_positive": true
      },
      "orders": {
        "count": 230,
        "change_percent": -2.1,
        "is_positive": false
      }
    }
  }
  ```

#### 2.3. دریافت گزارش تیکت‌ها

- **URL**: `/api/dashboard/tickets`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "tickets": {
      "weekly": [
        {
          "day": "Monday",
          "day_number": 12,
          "count": 5
        },
        {
          "day": "Tuesday",
          "day_number": 13,
          "count": 7
        },
        ...
      ],
      "summary": {
        "total": 45,
        "pending": 12,
        "answered": 28,
        "closed": 5
      }
    }
  }
  ```

#### 2.4. دریافت کاربران جدید

- **URL**: `/api/dashboard/new-users`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "new_users": [
      {
        "id": 125,
        "name": "علی محمدی",
        "email": "ali@example.com",
        "date": "2025-05-07 10:30:45",
        "profile_image": "http://localhost:8000/storage/profile_images/abc123.jpg"
      },
      ...
    ]
  }
  ```

#### 2.5. دریافت نمودار ثبت نام کاربران

- **URL**: `/api/dashboard/chart?period=week`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پارامتر‌های Query String**:
  - `period`: بازه زمانی (مقادیر ممکن: `week`، `month`، `quarter`، `year`، پیش‌فرض: `week`)
- **پاسخ موفق**:
  ```json
  {
    "chart": {
      "period": "week",
      "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      "datasets": [
        {
          "label": "کاربران",
          "data": [5, 8, 12, 7, 10, 15, 9]
        },
        {
          "label": "فروشندگان",
          "data": [2, 3, 1, 4, 2, 5, 3]
        }
      ]
    }
  }
  ```

#### 2.6. دریافت همه اطلاعات داشبورد

- **URL**: `/api/dashboard?period=week`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پارامتر‌های Query String**:
  - `period`: بازه زمانی نمودار (مقادیر ممکن: `week`، `month`، `quarter`، `year`، پیش‌فرض: `week`)
- **پاسخ موفق**: ترکیبی از تمام پاسخ‌های API های داشبورد در یک درخواست
  ```json
  {
    "info": {
      "date": { ... },
      "time": "14:30:45",
      ...
    },
    "stats": {
      "users": { ... },
      "sellers": { ... },
      "orders": { ... }
    },
    "tickets": {
      "weekly": [ ... ],
      "summary": { ... }
    },
    "new_users": [ ... ],
    "chart": {
      "period": "week",
      "labels": [ ... ],
      "datasets": [ ... ]
    }
  }
  ```

### 3. پروفایل

#### 3.1. دریافت اطلاعات پروفایل

- **URL**: `/api/profile`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "profile": {
      "id": 1,
      "first_name": "مدیر",
      "last_name": "کل",
      "email": "superadmin@example.com",
      "profile_image": "http://localhost:8000/storage/profile_images/abc123.jpg",
      "roles": ["super-admin"]
    }
  }
  ```

#### 3.2. به‌روزرسانی پروفایل

- **URL**: `/api/profile/update`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**: `FormData` شامل:
  - `first_name`: نام کاربر (اختیاری)
  - `last_name`: نام خانوادگی کاربر (اختیاری)
  - `email`: ایمیل جدید (اختیاری)
  - `profile_image`: فایل تصویر پروفایل (اختیاری)
- **پاسخ موفق**:
  ```json
  {
    "message": "اطلاعات پروفایل با موفقیت به‌روزرسانی شد.",
    "profile": {
      "id": 1,
      "first_name": "مدیر",
      "last_name": "کل",
      "email": "superadmin@example.com",
      "profile_image": "http://localhost:8000/storage/profile_images/abc123.jpg",
      "roles": ["super-admin"]
    }
  }
  ```

#### 3.3. تغییر رمز عبور

- **URL**: `/api/profile/change-password`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "current_password": "password",
    "password": "new_password",
    "password_confirmation": "new_password"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "رمز عبور با موفقیت تغییر یافت."
  }
  ```

### 4. جستجو

#### 4.1. جستجو در محتوای سایت

- **URL**: `/api/search?query=عبارت_جستجو`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "query": "عبارت_جستجو",
    "results": {
      "users": {
        "name": "کاربران",
        "count": 2,
        "items": [
          {
            "id": 1,
            "title": "مدیر کل",
            "subtitle": "superadmin@example.com",
            "url": "/admin/users/1"
          },
          ...
        ]
      },
      "products": {
        "name": "محصولات",
        "count": 3,
        "items": [
          {
            "id": 1,
            "title": "محصول 1",
            "subtitle": "توضیحات محصول...",
            "url": "/admin/products/1"
          },
          ...
        ]
      },
      ...
    },
    "total_count": 5
  }
  ```

### 5. درخواست‌ها

#### 5.1. دریافت لیست درخواست‌ها

- **URL**: `/api/requests`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "title": "درخواست شماره 1",
        "status": "در انتظار بررسی"
      },
      ...
    ]
  }
  ```

#### 5.2. پردازش درخواست

- **URL**: `/api/requests/{id}/process`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "message": "درخواست شماره {id} با موفقیت پردازش شد."
  }
  ```

#### 5.3. حذف درخواست

- **URL**: `/api/requests/{id}`
- **Method**: `DELETE`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "message": "درخواست شماره {id} با موفقیت حذف شد."
  }
  ```

## نکات فنی

1. **سطوح دسترسی**: سیستم دارای دو سطح دسترسی است:
   - `super-admin`: دسترسی به تمام بخش‌های سیستم
   - `admin`: دسترسی محدود به بخش درخواست‌ها

2. **فایل تصویر پروفایل**: برای آپلود تصویر پروفایل باید از `multipart/form-data` استفاده کنید.

3. **تاریخ شمسی**: API سیستم از کتابخانه `morilog/jalali` برای نمایش تاریخ شمسی استفاده می‌کند.

4. **امنیت**: همه درخواست‌ها باید از طریق HTTPS انجام شوند و توکن احراز هویت باید در هدر `Authorization` ارسال شود. 

### 6. نظرات خدمات‌دهندگان

#### 6.1. دریافت نظرات تایید شده یک خدمات‌دهنده

- **URL**: `/api/reviews/service-provider/{serviceProviderId}`
- **Method**: `GET`
- **پارامتر‌های Query String**:
  - `per_page`: تعداد نظرات در هر صفحه (پیش‌فرض: ۱۰)
  - `sort_by`: فیلد مرتب‌سازی (پیش‌فرض: `created_at`)
  - `sort_order`: ترتیب مرتب‌سازی (`asc` یا `desc`، پیش‌فرض: `desc`)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "user_id": 5,
          "service_provider_id": 3,
          "order_id": 42,
          "rating": 5,
          "comment": "خدمات بسیار عالی بود.",
          "status": "approved",
          "created_at": "2025-05-10T14:30:45.000000Z",
          "user": {
            "user_id": 5,
            "first_name": "علی",
            "last_name": "محمدی"
          }
        },
        ...
      ],
      "from": 1,
      "last_page": 3,
      "per_page": 10,
      "to": 10,
      "total": 25
    }
  }
  ```

#### 6.2. بررسی امکان ثبت نظر برای یک سفارش

- **URL**: `/api/reviews/can-submit`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پارامتر‌های Query String**:
  - `order_id`: شناسه سفارش (اجباری)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "can_submit": true
  }
  ```

#### 6.3. ثبت نظر جدید

- **URL**: `/api/reviews/submit`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "order_id": 42,
    "rating": 5,
    "comment": "خدمات بسیار عالی بود."
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.",
    "data": {
      "id": 1,
      "user_id": 5,
      "service_provider_id": 3,
      "order_id": 42,
      "rating": 5,
      "comment": "خدمات بسیار عالی بود.",
      "status": "pending",
      "created_at": "2025-05-10T14:30:45.000000Z"
    }
  }
  ```

#### 6.4. دریافت لیست نظرات برای ادمین

- **URL**: `/api/reviews/admin/list`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پارامتر‌های Query String**:
  - `status`: وضعیت نظرات (`pending`, `approved`, `rejected`)
  - `provider_id`: شناسه خدمات‌دهنده
  - `date_from`: تاریخ شروع (Y-m-d)
  - `date_to`: تاریخ پایان (Y-m-d)
  - `rating`: امتیاز (۱ تا ۵)
  - `sort_by`: فیلد مرتب‌سازی (پیش‌فرض: `created_at`)
  - `sort_order`: ترتیب مرتب‌سازی (`asc` یا `desc`، پیش‌فرض: `desc`)
  - `per_page`: تعداد نظرات در هر صفحه (پیش‌فرض: ۱۵)
  - `page`: شماره صفحه
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "user_id": 5,
          "service_provider_id": 3,
          "order_id": 42,
          "rating": 5,
          "comment": "خدمات بسیار عالی بود.",
          "status": "pending",
          "created_at": "2025-05-10T14:30:45.000000Z",
          "user": {
            "user_id": 5,
            "first_name": "علی",
            "last_name": "محمدی"
          },
          "serviceProvider": {
            "id": 3,
            "name": "شرکت خدماتی نمونه"
          },
          "order": {
            "order_id": 42,
            "status": "completed"
          }
        },
        ...
      ],
      "from": 1,
      "last_page": 3,
      "per_page": 15,
      "to": 15,
      "total": 40
    }
  }
  ```

#### 6.5. دریافت جزئیات یک نظر (برای ادمین)

- **URL**: `/api/reviews/admin/{reviewId}`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "id": 1,
      "user_id": 5,
      "service_provider_id": 3,
      "order_id": 42,
      "rating": 5,
      "comment": "خدمات بسیار عالی بود.",
      "status": "pending",
      "created_at": "2025-05-10T14:30:45.000000Z",
      "user": {
        "user_id": 5,
        "first_name": "علی",
        "last_name": "محمدی"
      },
      "serviceProvider": {
        "id": 3,
        "name": "شرکت خدماتی نمونه"
      },
      "order": {
        "order_id": 42,
        "status": "completed"
      },
      "statusLogs": [
        {
          "id": 1,
          "review_id": 1,
          "old_status": "pending",
          "new_status": "approved",
          "changed_by": 1,
          "changed_at": "2025-05-11T10:15:22.000000Z",
          "changedBy": {
            "user_id": 1,
            "first_name": "مدیر",
            "last_name": "سیستم"
          }
        }
      ]
    }
  }
  ```

#### 6.6. تایید یک نظر (برای ادمین)

- **URL**: `/api/reviews/admin/{reviewId}/approve`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "نظر با موفقیت تایید شد.",
    "data": {
      "id": 1,
      "status": "approved",
      "admin_id": 1
    }
  }
  ```

#### 6.7. رد یک نظر (برای ادمین)

- **URL**: `/api/reviews/admin/{reviewId}/reject`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "reason": "نظر حاوی محتوای نامناسب است."
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "نظر با موفقیت رد شد.",
    "data": {
      "id": 1,
      "status": "rejected",
      "rejection_reason": "نظر حاوی محتوای نامناسب است.",
      "admin_id": 1
    }
  }
  ```

### 7. تنظیمات چت و کنترل اسپم

#### 7.1. دریافت تنظیمات فعلی

- **URL**: `/api/settings/chat-filters`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "allow_chat_after_72_hours": false,
      "allow_chat_download": true,
      "allow_photo_only": false,
      "allow_view_names_only": false,
      "prevent_bad_words": true,
      "prevent_repeat_comments": true,
      "prevent_frequent_reviews": true,
      "limit_reviews_per_user": 5,
      "prevent_low_char_messages": true
    }
  }
  ```

#### 7.2. به‌روزرسانی تنظیمات

- **URL**: `/api/settings/chat-filters`
- **Method**: `PUT`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "allow_chat_after_72_hours": true,
    "prevent_bad_words": false,
    "limit_reviews_per_user": 10
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تنظیمات با موفقیت به‌روزرسانی شدند.",
    "updated": [
      "allow_chat_after_72_hours",
      "prevent_bad_words",
      "limit_reviews_per_user"
    ],
    "errors": []
  }
  ```

#### 7.3. بازنشانی تنظیمات به مقادیر پیش‌فرض

- **URL**: `/api/settings/chat-filters/reset`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تنظیمات با موفقیت به مقادیر پیش‌فرض بازنشانی شدند.",
    "updated": [
      "allow_chat_after_72_hours",
      "allow_chat_download",
      "allow_photo_only",
      "allow_view_names_only",
      "prevent_bad_words",
      "prevent_repeat_comments",
      "prevent_frequent_reviews",
      "limit_reviews_per_user",
      "prevent_low_char_messages"
    ],
    "errors": []
  }
  ```

#### 7.4. بررسی پیام از نظر اسپم بودن

- **URL**: `/api/settings/check-message`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "message": "این یک پیام تست است.",
    "order_id": 42
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "is_valid": true,
    "errors": []
  }
  ```
- **پاسخ با خطای اعتبارسنجی**:
  ```json
  {
    "status": true,
    "is_valid": false,
    "errors": [
      "پیام شما باید حداقل 5 کاراکتر داشته باشد.",
      "شما بیش از 5 نظر در روز نمی‌توانید ثبت کنید."
    ]
  }
  ```

### 8. مدیریت بنر و اسلایدر

#### 8.1. دریافت فهرست آیتم‌های بنر یا اسلایدر

- **URL**: `/api/media-items?type=banner` یا `/api/media-items?type=slider`
- **Method**: `GET`
- **پارامترهای Query String**:
  - `type`: نوع آیتم (`banner` یا `slider`)
  - `active_only`: فقط آیتم‌های فعال نمایش داده شود (`true` یا `false`)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": [
      {
        "id": 1,
        "type": "banner",
        "title": "تخفیف ویژه",
        "image_path": "media/banners/abc123.jpg",
        "image_url": "http://example.com/storage/media/banners/abc123.jpg",
        "link": "http://example.com/special-offers",
        "order": 1,
        "is_active": true,
        "created_by": 1,
        "created_at": "2025-05-14T12:30:45.000000Z",
        "updated_at": "2025-05-14T12:30:45.000000Z"
      },
      {
        "id": 2,
        "type": "banner",
        "title": "محصولات جدید",
        "image_path": "media/banners/def456.jpg",
        "image_url": "http://example.com/storage/media/banners/def456.jpg",
        "link": "http://example.com/new-products",
        "order": 2,
        "is_active": true,
        "created_by": 1,
        "created_at": "2025-05-14T12:35:45.000000Z",
        "updated_at": "2025-05-14T12:35:45.000000Z"
      }
    ]
  }
  ```

#### 8.2. دریافت جزئیات یک آیتم

- **URL**: `/api/media-items/{id}`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "id": 1,
      "type": "banner",
      "title": "تخفیف ویژه",
      "image_path": "media/banners/abc123.jpg",
      "image_url": "http://example.com/storage/media/banners/abc123.jpg",
      "link": "http://example.com/special-offers",
      "order": 1,
      "is_active": true,
      "created_by": 1,
      "created_at": "2025-05-14T12:30:45.000000Z",
      "updated_at": "2025-05-14T12:30:45.000000Z",
      "creator": {
        "user_id": 1,
        "first_name": "مدیر",
        "last_name": "سیستم"
      }
    }
  }
  ```

#### 8.3. ایجاد آیتم جدید

- **URL**: `/api/media-items`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**: `multipart/form-data` شامل:
  - `type`: نوع آیتم (`banner` یا `slider`)
  - `title`: عنوان آیتم
  - `image`: فایل تصویر (jpeg, png, jpg)
  - `link`: لینک مقصد (اختیاری)
  - `order`: ترتیب نمایش
  - `is_active`: وضعیت فعال/غیرفعال (boolean)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "بنر با موفقیت ایجاد شد.",
    "data": {
      "id": 3,
      "type": "banner",
      "title": "تخفیف تابستانه",
      "image_path": "media/banners/ghi789.jpg",
      "image_url": "http://example.com/storage/media/banners/ghi789.jpg",
      "link": "http://example.com/summer-offers",
      "order": 3,
      "is_active": true,
      "created_by": 1,
      "created_at": "2025-05-14T14:10:45.000000Z",
      "updated_at": "2025-05-14T14:10:45.000000Z"
    }
  }
  ```

#### 8.4. به‌روزرسانی آیتم

- **URL**: `/api/media-items/{id}`
- **Method**: `PUT`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**: `multipart/form-data` شامل:
  - `title`: عنوان آیتم
  - `image`: فایل تصویر (اختیاری، فقط در صورت تغییر)
  - `link`: لینک مقصد (اختیاری)
  - `order`: ترتیب نمایش
  - `is_active`: وضعیت فعال/غیرفعال (boolean)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "بنر با موفقیت به‌روزرسانی شد.",
    "data": {
      "id": 3,
      "type": "banner",
      "title": "تخفیف ویژه تابستان",
      "image_path": "media/banners/ghi789.jpg",
      "image_url": "http://example.com/storage/media/banners/ghi789.jpg",
      "link": "http://example.com/special-summer-offers",
      "order": 2,
      "is_active": true,
      "created_by": 1,
      "created_at": "2025-05-14T14:10:45.000000Z",
      "updated_at": "2025-05-14T14:25:18.000000Z"
    }
  }
  ```

#### 8.5. حذف آیتم

- **URL**: `/api/media-items/{id}`
- **Method**: `DELETE`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "بنر با موفقیت حذف شد."
  }
  ```

#### 8.6. تغییر وضعیت فعال/غیرفعال

- **URL**: `/api/media-items/{id}/status`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "is_active": false
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "بنر با موفقیت غیرفعال شد.",
    "data": {
      "id": 2,
      "type": "banner",
      "title": "محصولات جدید",
      "image_path": "media/banners/def456.jpg",
      "image_url": "http://example.com/storage/media/banners/def456.jpg",
      "link": "http://example.com/new-products",
      "order": 2,
      "is_active": false,
      "created_by": 1,
      "created_at": "2025-05-14T12:35:45.000000Z",
      "updated_at": "2025-05-14T15:20:12.000000Z"
    }
  }
  ```

### 9. سیستم حسابداری و مدیریت تراکنش‌ها

این بخش شامل API‌های مربوط به سیستم حسابداری و مدیریت تراکنش‌های مالی می‌باشد.

#### 9.1. دریافت خلاصه وضعیت مالی

- **URL**: `/api/accounting/summary`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN` (برای دسترسی به اطلاعات جزئی‌تر)
- **پارامتر‌های Query String**:
  - `period`: نوع دوره زمانی (`daily`, `monthly`, `yearly`، پیش‌فرض: `daily`)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "date": "2023-05-01",
      "total_balance": 250000000,
      "total_revenue": 15000000,
      "total_withdrawals": 20000000,
      "total_pending_withdrawals": 5000000,
      "period": "daily",
      "trends": {
        "balance_growth": 5.25,
        "revenue_growth": 3.15,
        "withdrawals_growth": 1.75
      }
    }
  }
  ```

#### 9.2. دریافت نمودار درآمد

- **URL**: `/api/accounting/revenue-chart`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN` (برای دسترسی به اطلاعات جزئی‌تر)
- **پارامتر‌های Query String**:
  - `period`: نوع دوره (`month`, `year`، پیش‌فرض: `month`)
  - `limit`: تعداد آیتم‌ها (پیش‌فرض: 12)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "labels": ["فروردین 1402", "اردیبهشت 1402", "خرداد 1402", "تیر 1402"],
      "data": [12000000, 15000000, 18000000, 22000000],
      "period": "month",
      "total": 67000000
    }
  }
  ```

#### 9.3. دریافت لیست تراکنش‌ها

- **URL**: `/api/accounting/transactions`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پارامتر‌های Query String**:
  - `status`: فیلتر بر اساس وضعیت (`pending`, `approved`, `rejected`, `settled`)
  - `type`: فیلتر بر اساس نوع (`withdraw_user`, `withdraw_provider`, `deposit`, `fee`, `refund`, `settlement`)
  - `user_id`: فیلتر بر اساس شناسه کاربر
  - `provider_id`: فیلتر بر اساس شناسه خدمات‌دهنده
  - `date_from`: فیلتر از تاریخ (فرمت: Y-m-d)
  - `date_to`: فیلتر تا تاریخ (فرمت: Y-m-d)
  - `sort_by`: مرتب‌سازی بر اساس (`created_at`, `amount`, ...، پیش‌فرض: `created_at`)
  - `sort_order`: ترتیب مرتب‌سازی (`asc`, `desc`، پیش‌فرض: `desc`)
  - `per_page`: تعداد آیتم در هر صفحه (پیش‌فرض: 15)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "user_id": 123,
          "provider_id": null,
          "type": "withdraw_user",
          "amount": "1500000",
          "status": "pending",
          "reference_id": "REF123456",
          "bank_account": "IR123456789",
          "tracking_code": null,
          "created_at": "2023-05-01T10:30:00",
          "updated_at": "2023-05-01T10:30:00",
          "user": {
            "user_id": 123,
            "first_name": "محمد",
            "last_name": "محمدی",
            "email": "user@example.com"
          }
        }
      ],
      "first_page_url": "...",
      "from": 1,
      "last_page": 5,
      "last_page_url": "...",
      "links": [...],
      "next_page_url": "...",
      "path": "...",
      "per_page": 15,
      "prev_page_url": null,
      "to": 15,
      "total": 75
    }
  }
  ```

#### 9.4. دریافت جزئیات یک تراکنش

- **URL**: `/api/accounting/transactions/{id}`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "id": 1,
      "user_id": 123,
      "provider_id": null,
      "type": "withdraw_user",
      "type_fa": "برداشت کاربر",
      "amount": "1500000",
      "status": "pending",
      "status_fa": "در انتظار",
      "reference_id": "REF123456",
      "bank_account": "IR123456789",
      "tracking_code": null,
      "created_at": "2023-05-01T10:30:00",
      "updated_at": "2023-05-01T10:30:00",
      "user_name": "محمد محمدی",
      "user_mobile": "09123456789",
      "user_email": "user@example.com",
      "metadata": {
        "description": "درخواست برداشت وجه"
      }
    }
  }
  ```

#### 9.5. تایید یک تراکنش

- **URL**: `/api/accounting/transactions/{id}/approve`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "tracking_code": "TR123456789"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تراکنش با موفقیت تایید شد."
  }
  ```

#### 9.6. رد یک تراکنش

- **URL**: `/api/accounting/transactions/{id}/reject`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "reason": "عدم تطابق اطلاعات حساب بانکی"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تراکنش با موفقیت رد شد."
  }
  ```

#### 9.7. تسویه یک تراکنش

- **URL**: `/api/accounting/transactions/{id}/settle`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "tracking_code": "TR987654321"
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تراکنش با موفقیت تسویه شد."
  }
  ```

#### 9.8. ایجاد درخواست برداشت کاربر

- **URL**: `/api/accounting/user-withdrawal`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "user_id": 123,
    "amount": 1500000,
    "bank_account": "IR123456789",
    "metadata": {
      "description": "برداشت ماهانه"
    }
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "درخواست برداشت با موفقیت ایجاد شد.",
    "data": {
      "id": 10,
      "user_id": 123,
      "type": "withdraw_user",
      "amount": "1500000",
      "status": "pending",
      "bank_account": "IR123456789",
      "created_at": "2023-05-01T10:30:00",
      "updated_at": "2023-05-01T10:30:00"
    }
  }
  ```

#### 9.9. ایجاد درخواست برداشت خدمات‌دهنده

- **URL**: `/api/accounting/provider-withdrawal`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
  ```json
  {
    "provider_id": 456,
    "amount": 2000000,
    "bank_account": "IR987654321",
    "metadata": {
      "description": "تسویه حساب دوره‌ای"
    }
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "درخواست برداشت با موفقیت ایجاد شد.",
    "data": {
      "id": 11,
      "provider_id": 456,
      "type": "withdraw_provider",
      "amount": "2000000",
      "status": "pending",
      "bank_account": "IR987654321",
      "created_at": "2023-05-01T11:45:00",
      "updated_at": "2023-05-01T11:45:00"
    }
  }
  ```

### 10. مدیریت تنظیمات تبلیغات (Ad Settings)

این API برای مدیریت تنظیمات سرویس‌های تبلیغاتی ثالث (مانند یکتانت و تپسل) استفاده می‌شود.

#### 10.1. سرویس‌های تبلیغاتی پشتیبانی شده
- `yektanet`: سرویس تبلیغاتی یکتانت
- `tapsell`: سرویس تبلیغاتی تپسل

#### 10.2. دریافت لیست تنظیمات تبلیغات

- **URL**: `/api/ad-settings`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN` (اختیاری برای دسترسی به تنظیمات غیرفعال)
- **پارامترهای Query String**:
  - `placement` (اختیاری): فیلتر بر اساس محل نمایش (مثلاً header، sidebar)
  - `service` (اختیاری): فیلتر بر اساس سرویس (yektanet یا tapsell)
  - `active_only` (اختیاری): مقدار `true` برای نمایش فقط تنظیمات فعال
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": [
      {
        "id": 1,
        "service": "yektanet",
        "placement": "header",
        "position_id": "12345",
        "is_active": true,
        "order": 1,
        "config": {
          "api_key": "yk-12345-67890"
        },
        "created_by": 1,
        "created_at": "2023-01-01 00:00:00",
        "updated_at": "2023-01-01 00:00:00"
      }
    ]
  }
  ```

#### 10.3. دریافت جزئیات یک تنظیم تبلیغات

- **URL**: `/api/ad-settings/{id}`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "id": 1,
      "service": "yektanet",
      "placement": "header",
      "position_id": "12345",
      "is_active": true,
      "order": 1,
      "config": {
        "api_key": "yk-12345-67890"
      },
      "created_by": 1,
      "created_at": "2023-01-01 00:00:00",
      "updated_at": "2023-01-01 00:00:00"
    }
  }
  ```

#### 10.4. ایجاد تنظیم تبلیغات جدید

- **URL**: `/api/ad-settings`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body (application/json)**:
  ```json
  {
    "service": "yektanet",
    "placement": "header",
    "position_id": "12345",
    "order": 1,
    "is_active": true,
    "config": {
      "api_key": "yk-12345-67890"
    }
  }
  ```
- **نکات**:
  - برای سرویس یکتانت، فیلد `config.api_key` الزامی است
  - برای سرویس تپسل، فیلد `config.app_id` الزامی است
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تنظیم تبلیغات با موفقیت ایجاد شد.",
    "data": {
      "id": 1,
      "service": "yektanet",
      "placement": "header",
      "position_id": "12345",
      "is_active": true,
      "order": 1,
      "config": {
        "api_key": "yk-12345-67890"
      },
      "created_by": 1,
      "created_at": "2023-01-01 00:00:00",
      "updated_at": "2023-01-01 00:00:00"
    }
  }
  ```

#### 10.5. به‌روزرسانی تنظیم تبلیغات

- **URL**: `/api/ad-settings/{id}`
- **Method**: `PUT`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body (application/json)**:
  ```json
  {
    "placement": "sidebar",
    "position_id": "67890",
    "order": 2,
    "is_active": true,
    "config": {
      "api_key": "yk-67890-12345"
    }
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تنظیم تبلیغات با موفقیت به‌روزرسانی شد.",
    "data": {
      "id": 1,
      "service": "yektanet",
      "placement": "sidebar",
      "position_id": "67890",
      "is_active": true,
      "order": 2,
      "config": {
        "api_key": "yk-67890-12345"
      },
      "created_by": 1,
      "created_at": "2023-01-01 00:00:00",
      "updated_at": "2023-01-02 00:00:00"
    }
  }
  ```

#### 10.6. حذف تنظیم تبلیغات

- **URL**: `/api/ad-settings/{id}`
- **Method**: `DELETE`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تنظیم تبلیغات با موفقیت حذف شد."
  }
  ```

#### 10.7. تغییر وضعیت فعال/غیرفعال تنظیم تبلیغات

- **URL**: `/api/ad-settings/{id}/status`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body (application/json)**:
  ```json
  {
    "is_active": true
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "تنظیم تبلیغات با موفقیت فعال شد.",
    "data": {
      "id": 1,
      "service": "yektanet",
      "placement": "header",
      "position_id": "12345",
      "is_active": true,
      "order": 1,
      "config": {
        "api_key": "yk-12345-67890"
      },
      "created_by": 1,
      "created_at": "2023-01-01 00:00:00",
      "updated_at": "2023-01-02 00:00:00"
    }
  }
  ```

### 11. مدیریت اطلاعیه‌ها و قوانین (Notices & Policies)

این بخش شامل API‌های مربوط به مدیریت اطلاعیه‌ها و قوانین سایت است. از این API‌ها برای ایجاد، ویرایش، انتشار و مدیریت اطلاعیه‌ها و قوانین استفاده می‌شود.

#### 11.1. دریافت لیست اطلاعیه‌ها

- **URL**: `/api/notices`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN` (اختیاری برای دسترسی به اطلاعیه‌های غیرعمومی)
- **پارامترهای Query String**:
  - `forAdmin` (اختیاری): مقدار `true` برای نمایش تمام اطلاعیه‌ها (فقط برای ادمین و مدیر محتوا)
  - `type` (اختیاری): فیلتر بر اساس نوع اطلاعیه (`announcement` یا `policy`)
  - `status` (اختیاری): فیلتر بر اساس وضعیت (`draft`، `published` یا `archived`)
  - `sort_by` (اختیاری): معیار مرتب‌سازی (`publish_at`، `version` یا `created_at`)
  - `sort_order` (اختیاری): ترتیب مرتب‌سازی (`asc` یا `desc`)
  - `per_page` (اختیاری): تعداد آیتم در هر صفحه
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "type": "announcement",
          "title": "خبر مهم",
          "body": "متن خبر...",
          "target": ["all"],
          "status": "published",
          "publish_at": "2023-05-15 14:30:00",
          "version": 1,
          "created_by": 1,
          "updated_by": null,
          "created_at": "2023-05-15 12:00:00",
          "updated_at": "2023-05-15 12:00:00",
          "creator": {
            "user_id": 1,
            "first_name": "مدیر",
            "last_name": "سیستم"
          }
        },
        ...
      ],
      "from": 1,
      "last_page": 4,
      "per_page": 15,
      "to": 15,
      "total": 52
    }
  }
  ```

#### 11.2. دریافت جزئیات یک اطلاعیه

- **URL**: `/api/notices/{id}`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN` (اختیاری برای دسترسی به اطلاعیه‌های غیرعمومی)
- **پارامترهای Query String**:
  - `forAdmin` (اختیاری): مقدار `true` برای نمایش جزئیات کامل (فقط برای ادمین و مدیر محتوا)
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "id": 1,
      "type": "announcement",
      "title": "خبر مهم",
      "body": "متن خبر...",
      "target": ["all"],
      "status": "published",
      "publish_at": "2023-05-15 14:30:00",
      "version": 1,
      "created_by": 1,
      "updated_by": null,
      "created_at": "2023-05-15 12:00:00",
      "updated_at": "2023-05-15 12:00:00",
      "is_visible": true,
      "creator_name": "مدیر سیستم",
      "editor_name": null,
      "is_viewed": true
    }
  }
  ```

#### 11.3. ایجاد اطلاعیه جدید

- **URL**: `/api/notices`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body (application/json)**:
  ```json
  {
    "type": "announcement",
    "title": "خبر مهم",
    "body": "متن خبر...",
    "target": ["all"],
    "status": "draft",
    "publish_at": "2023-05-20 14:30:00"
  }
  ```
- **نکات**:
  - فیلد `target` می‌تواند آرایه‌ای از شناسه‌های کاربران یا `["all"]` برای همه کاربران باشد
  - فیلد `publish_at` اختیاری است و فقط برای اطلاعیه‌های با وضعیت `published` استفاده می‌شود
  - برای قوانین (`type: "policy"`): شماره نسخه به صورت خودکار افزایش می‌یابد
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "اطلاعیه با موفقیت ایجاد شد.",
    "data": {
      "id": 1,
      "type": "announcement",
      "title": "خبر مهم",
      "body": "متن خبر...",
      "target": ["all"],
      "status": "draft",
      "publish_at": null,
      "version": 1,
      "created_by": 1,
      "updated_by": null,
      "created_at": "2023-05-15 12:00:00",
      "updated_at": "2023-05-15 12:00:00"
    }
  }
  ```

#### 11.4. به‌روزرسانی اطلاعیه

- **URL**: `/api/notices/{id}`
- **Method**: `PUT`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body (application/json)**:
  ```json
  {
    "title": "عنوان به‌روز شده",
    "body": "متن به‌روز شده...",
    "target": ["all"],
    "status": "draft"
  }
  ```
- **نکات**:
  - برای قوانین منتشر شده (`type: "policy"` و `status: "published"`): یک نسخه جدید ایجاد می‌شود
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "اطلاعیه با موفقیت به‌روزرسانی شد.",
    "data": {
      "id": 1,
      "type": "announcement",
      "title": "عنوان به‌روز شده",
      "body": "متن به‌روز شده...",
      "target": ["all"],
      "status": "draft",
      "publish_at": null,
      "version": 1,
      "created_by": 1,
      "updated_by": 1,
      "created_at": "2023-05-15 12:00:00",
      "updated_at": "2023-05-15 14:30:00"
    }
  }
  ```

#### 11.5. انتشار اطلاعیه

- **URL**: `/api/notices/{id}/publish`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **Body (application/json)**:
  ```json
  {
    "publish_at": "2023-05-20 14:30:00"
  }
  ```
- **نکات**:
  - فیلد `publish_at` اختیاری است. اگر ارسال نشود، اطلاعیه فوراً منتشر می‌شود
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "اطلاعیه برای انتشار در تاریخ 2023-05-20 14:30 زمان‌بندی شد.",
    "data": {
      "id": 1,
      "type": "announcement",
      "title": "عنوان به‌روز شده",
      "body": "متن به‌روز شده...",
      "target": ["all"],
      "status": "published",
      "publish_at": "2023-05-20 14:30:00",
      "version": 1,
      "created_by": 1,
      "updated_by": 1,
      "created_at": "2023-05-15 12:00:00",
      "updated_at": "2023-05-15 15:00:00"
    }
  }
  ```

#### 11.6. آرشیو کردن اطلاعیه

- **URL**: `/api/notices/{id}/archive`
- **Method**: `PATCH`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "اطلاعیه با موفقیت آرشیو شد.",
    "data": {
      "id": 1,
      "type": "announcement",
      "title": "عنوان به‌روز شده",
      "body": "متن به‌روز شده...",
      "target": ["all"],
      "status": "archived",
      "publish_at": "2023-05-20 14:30:00",
      "version": 1,
      "created_by": 1,
      "updated_by": 1,
      "created_at": "2023-05-15 12:00:00",
      "updated_at": "2023-05-15 16:00:00"
    }
  }
  ```

#### 11.7. حذف اطلاعیه

- **URL**: `/api/notices/{id}`
- **Method**: `DELETE`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "message": "اطلاعیه با موفقیت حذف شد."
  }
  ```

#### 11.8. دریافت تعداد اطلاعیه‌های خوانده نشده

- **URL**: `/api/notices/unread/count`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "status": true,
    "data": {
      "unread_count": 3
    }
  }
  ```
