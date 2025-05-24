# مستندات API امنیت حساب کاربری و نوتیفیکیشن‌ها

## 1. API های نوتیفیکیشن‌ها (سابقه پیام‌های کاربر)

### 1.1. دریافت لیست نوتیفیکیشن‌ها

این API لیست نوتیفیکیشن‌های کاربر را به صورت صفحه‌بندی شده برمی‌گرداند.

- **URL**: `/api/notifications`
- **متد**: `GET`
- **هدر**: `Authorization: Bearer YOUR_TOKEN`
- **پارامترهای Query String**:
  - `per_page`: تعداد آیتم‌ها در هر صفحه (اختیاری، پیش‌فرض: 15)
  - `page`: شماره صفحه (اختیاری، پیش‌فرض: 1)
- **پاسخ موفق**:
  ```json
  {
    "success": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": "8a7b6c5d4e3f2g1h",
          "type": "App\\Notifications\\MessageReceived",
          "notifiable_type": "App\\Models\\User",
          "notifiable_id": 123,
          "data": {
            "message": "شما یک پیام جدید دریافت کردید",
            "sender_name": "علی محمدی",
            "sender_id": 456
          },
          "read_at": null,
          "created_at": "2024-05-10T12:30:45.000000Z",
          "updated_at": "2024-05-10T12:30:45.000000Z"
        },
        ...
      ],
      "first_page_url": "http://example.com/api/notifications?page=1",
      "from": 1,
      "last_page": 3,
      "last_page_url": "http://example.com/api/notifications?page=3",
      "links": [...],
      "next_page_url": "http://example.com/api/notifications?page=2",
      "path": "http://example.com/api/notifications",
      "per_page": 15,
      "prev_page_url": null,
      "to": 15,
      "total": 42
    }
  }
  ```

### 1.2. نشانه‌گذاری یک نوتیفیکیشن به عنوان خوانده شده

این API یک نوتیفیکیشن خاص را به عنوان خوانده شده علامت‌گذاری می‌کند.

- **URL**: `/api/notifications/{notificationId}/mark-as-read`
- **متد**: `POST`
- **هدر**: `Authorization: Bearer YOUR_TOKEN`
- **پارامترهای مسیر**:
  - `notificationId`: شناسه یکتای نوتیفیکیشن (UUID)
- **پاسخ موفق**:
  ```json
  {
    "success": true,
    "message": "نوتیفیکیشن به عنوان خوانده شده علامت‌گذاری شد."
  }
  ```
- **پاسخ خطا (404)**:
  ```json
  {
    "success": false,
    "message": "نوتیفیکیشن یافت نشد."
  }
  ```

### 1.3. نشانه‌گذاری همه نوتیفیکیشن‌ها به عنوان خوانده شده

این API تمام نوتیفیکیشن‌های خوانده نشده کاربر را به عنوان خوانده شده علامت‌گذاری می‌کند.

- **URL**: `/api/notifications/mark-all-as-read`
- **متد**: `POST`
- **هدر**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "success": true,
    "message": "تمام نوتیفیکیشن‌های خوانده نشده به عنوان خوانده شده علامت‌گذاری شدند."
  }
  ```

## 2. API های تنظیمات ورود

### 2.1. دریافت تنظیمات ورود کاربر

این API تنظیمات فعلی ورود کاربر را برمی‌گرداند.

- **URL**: `/api/account-security/login-settings`
- **متد**: `GET`
- **هدر**: `Authorization: Bearer YOUR_TOKEN`
- **پاسخ موفق**:
  ```json
  {
    "success": true,
    "data": {
      "login_preference": "password",
      "has_verified_email": true,
      "has_verified_phone": false,
      "email": "user@example.com",
      "phone_number": "09123456789"
    }
  }
  ```

### 2.2. به‌روزرسانی تنظیمات ورود کاربر

این API تنظیمات ورود کاربر را به‌روزرسانی می‌کند.

- **URL**: `/api/account-security/login-settings`
- **متد**: `POST`
- **هدر**: `Authorization: Bearer YOUR_TOKEN`
- **بدنه درخواست**:
  ```json
  {
    "login_preference": "email_otp"
  }
  ```
- **پارامترهای بدنه درخواست**:
  - `login_preference` (الزامی): روش ورود انتخابی. مقادیر مجاز: `password`، `email_otp`، `phone_otp`
- **پاسخ موفق**:
  ```json
  {
    "success": true,
    "message": "تنظیمات ورود با موفقیت به‌روزرسانی شد.",
    "data": {
      "login_preference": "email_otp"
    }
  }
  ```
- **پاسخ خطا (422) - ایمیل تایید نشده**:
  ```json
  {
    "success": false,
    "message": "برای انتخاب ورود با ایمیل، باید ابتدا ایمیل خود را تایید کنید."
  }
  ```
- **پاسخ خطا (422) - شماره تلفن تایید نشده**:
  ```json
  {
    "success": false,
    "message": "برای انتخاب ورود با شماره تلفن، باید ابتدا شماره تلفن خود را تایید کنید."
  }
  ```

## 3. API های دستگاه‌های اخیر

### 3.1. دریافت لیست دستگاه‌های اخیر ورود کاربر

این API لیست دستگاه‌هایی که کاربر اخیراً با آن‌ها وارد حساب کاربری خود شده را برمی‌گرداند.

- **URL**: `/api/account-security/devices`
- **متد**: `GET`
- **هدر**: `Authorization: Bearer YOUR_TOKEN`
- **پارامترهای Query String**:
  - `per_page`: تعداد آیتم‌ها در هر صفحه (اختیاری، پیش‌فرض: 10)
  - `page`: شماره صفحه (اختیاری، پیش‌فرض: 1)
- **پاسخ موفق**:
  ```json
  {
    "success": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "user_id": 123,
          "ip_address": "192.168.1.1",
          "device_type": "desktop",
          "browser_name": "Chrome",
          "platform_name": "Windows",
          "login_at": "2024-05-10T15:30:45.000000Z",
          "location": null,
          "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
          "created_at": "2024-05-10T15:30:45.000000Z",
          "updated_at": "2024-05-10T15:30:45.000000Z"
        },
        {
          "id": 2,
          "user_id": 123,
          "ip_address": "192.168.1.100",
          "device_type": "mobile",
          "browser_name": "Mobile Safari",
          "platform_name": "iOS",
          "login_at": "2024-05-09T20:15:30.000000Z",
          "location": null,
          "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1",
          "created_at": "2024-05-09T20:15:30.000000Z",
          "updated_at": "2024-05-09T20:15:30.000000Z"
        },
        ...
      ],
      "first_page_url": "http://example.com/api/account-security/devices?page=1",
      "from": 1,
      "last_page": 2,
      "last_page_url": "http://example.com/api/account-security/devices?page=2",
      "links": [...],
      "next_page_url": "http://example.com/api/account-security/devices?page=2",
      "path": "http://example.com/api/account-security/devices",
      "per_page": 10,
      "prev_page_url": null,
      "to": 10,
      "total": 15
    }
  }
  ```

## نکات پیاده‌سازی

- **احراز هویت**: تمام API ها نیاز به احراز هویت با استفاده از توکن Bearer دارند.
- **ثبت ورود‌های کاربر**: هر بار که کاربر وارد سیستم می‌شود، اطلاعات دستگاه او به صورت خودکار در جدول `login_histories` ذخیره می‌شود.
- **روش‌های ورود**:
  - **password**: ورود با استفاده از رمز عبور (روش پیش‌فرض)
  - **email_otp**: ورود با استفاده از کد یکبار مصرف ارسال شده به ایمیل (نیاز به ایمیل تایید شده دارد)
  - **phone_otp**: ورود با استفاده از کد یکبار مصرف ارسال شده به شماره تلفن (نیاز به شماره تلفن تایید شده دارد) 



