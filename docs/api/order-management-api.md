# API مدیریت سفارشات

این سند تمام نقاط پایانی (Endpoints) مربوط به مدیریت سفارشات و گفتگوهای آنها را توضیح می‌دهد.

## احراز هویت

تمامی درخواست‌ها به API نیاز به توکن احراز هویت دارند. توکن باید در هدر `Authorization` به صورت زیر ارسال شود:

```
Authorization: Bearer YOUR_TOKEN
```

## نقاط پایانی API

### دریافت لیست سفارشات کاربر

دریافت لیست سفارشات کاربر جاری (احراز هویت شده)

**URL**: `/api/v1/user/orders`

**Method**: `GET`

**پارامترهای درخواست (Query Parameters)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| per_page | عدد | تعداد رکوردهای هر صفحه (پیش‌فرض: 10) |
| status | متن | فیلتر براساس وضعیت (pending, processing, completed, cancelled, rejected) |
| type | متن | فیلتر براساس نوع سفارش (service, product) |

**پاسخ موفق**:

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "order_id": 123,
                "user_id": 456,
                "seller_id": 789,
                "order_type": "service",
                "status": "processing",
                "status_fa": "در حال انجام",
                "description": "توضیحات سفارش",
                "total_amount": 1500000,
                "created_at": "2023-01-01T12:00:00.000000Z",
                "updated_at": "2023-01-02T14:30:00.000000Z",
                "seller": {
                    "seller_id": 789,
                    "shop_name": "طراحی لوگو",
                    "user_id": 987
                },
                "last_message": {
                    "message_id": 123,
                    "order_id": 123,
                    "user_id": 456,
                    "message": "پیام نمونه",
                    "created_at": "2023-01-03T10:15:00.000000Z"
                }
            }
        ],
        "first_page_url": "http://example.com/api/v1/user/orders?page=1",
        "from": 1,
        "last_page": 3,
        "last_page_url": "http://example.com/api/v1/user/orders?page=3",
        "links": [
            {
                "url": null,
                "label": "&laquo; قبلی",
                "active": false
            },
            {
                "url": "http://example.com/api/v1/user/orders?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "http://example.com/api/v1/user/orders?page=2",
                "label": "2",
                "active": false
            },
            {
                "url": "http://example.com/api/v1/user/orders?page=3",
                "label": "3",
                "active": false
            },
            {
                "url": "http://example.com/api/v1/user/orders?page=2",
                "label": "بعدی &raquo;",
                "active": false
            }
        ],
        "next_page_url": "http://example.com/api/v1/user/orders?page=2",
        "path": "http://example.com/api/v1/user/orders",
        "per_page": 10,
        "prev_page_url": null,
        "to": 10,
        "total": 25
    }
}
```

### دریافت جزئیات یک سفارش

دریافت جزئیات کامل یک سفارش خاص

**URL**: `/api/v1/user/orders/{orderId}`

**Method**: `GET`

**پارامترهای مسیر (Path Parameters)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| orderId | عدد | شناسه یکتای سفارش |

**پاسخ موفق**:

```json
{
    "success": true,
    "data": {
        "order_id": 123,
        "user_id": 456,
        "seller_id": 789,
        "order_type": "service",
        "status": "processing",
        "status_fa": "در حال انجام",
        "description": "توضیحات سفارش",
        "attachment_url": "https://example.com/attachments/123.jpg",
        "total_amount": 1500000,
        "created_at": "2023-01-01T12:00:00.000000Z",
        "updated_at": "2023-01-02T14:30:00.000000Z",
        "seller": {
            "seller_id": 789,
            "user_id": 987,
            "shop_name": "طراحی لوگو"
        },
        "items": [
            {
                "order_item_id": 321,
                "order_id": 123,
                "product_id": 111,
                "quantity": 1,
                "price": 1500000,
                "product": {
                    "product_id": 111,
                    "name": "طراحی لوگو سازمانی",
                    "image": "https://example.com/products/111.jpg"
                }
            }
        ],
        "status_history": [
            {
                "id": 234,
                "order_id": 123,
                "status": "pending",
                "notes": "سفارش ایجاد شد",
                "created_by": 456,
                "created_at": "2023-01-01T12:00:00.000000Z"
            },
            {
                "id": 235,
                "order_id": 123,
                "status": "processing",
                "notes": "سفارش در حال پردازش است",
                "created_by": 789,
                "created_at": "2023-01-02T14:30:00.000000Z"
            }
        ]
    }
}
```

### دریافت گفتگوهای یک سفارش

دریافت لیست پیام‌های مربوط به یک سفارش

**URL**: `/api/v1/user/orders/{orderId}/conversation`

**Method**: `GET`

**پارامترهای مسیر (Path Parameters)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| orderId | عدد | شناسه یکتای سفارش |

**پاسخ موفق**:

```json
{
    "success": true,
    "data": [
        {
            "message_id": 123,
            "order_id": 123,
            "user_id": 456,
            "message": "سلام، این پیام اول من است",
            "attachment_url": null,
            "created_at": "2023-01-03T10:15:00.000000Z",
            "user": {
                "user_id": 456,
                "first_name": "علی",
                "last_name": "رضایی",
                "profile_image": "https://example.com/profiles/456.jpg"
            }
        },
        {
            "message_id": 124,
            "order_id": 123,
            "user_id": 789,
            "message": "سلام، من پیشنویس اولیه را آماده کردم",
            "attachment_url": "https://example.com/attachments/draft.pdf",
            "created_at": "2023-01-04T11:30:00.000000Z",
            "user": {
                "user_id": 789,
                "first_name": "ریحانه",
                "last_name": "فلاحی",
                "profile_image": "https://example.com/profiles/789.jpg"
            }
        }
    ]
}
```

**پاسخ در صورت حذف گفتگوها (به دلیل گذشت زمان)**:

```json
{
    "success": true,
    "message": "گفتگوهای این سفارش به دلیل گذشت زمان حذف شده‌اند",
    "data": []
}
```

### ارسال پیام جدید در گفتگوی سفارش

ارسال پیام جدید در گفتگوی مربوط به یک سفارش

**URL**: `/api/v1/user/orders/{orderId}/message`

**Method**: `POST`

**پارامترهای مسیر (Path Parameters)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| orderId | عدد | شناسه یکتای سفارش |

**پارامترهای درخواست (Request Body)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| message | متن | متن پیام (الزامی در صورت عدم وجود فایل پیوست) |
| attachment | فایل | فایل پیوست (اختیاری) |

**پاسخ موفق**:

```json
{
    "success": true,
    "message": "پیام با موفقیت ارسال شد",
    "data": {
        "message_id": 125,
        "order_id": 123,
        "user_id": 456,
        "message": "لطفا در لوگو از رنگ آبی بیشتر استفاده کنید",
        "attachment_url": null,
        "created_at": "2023-01-05T09:45:00.000000Z"
    }
}
```

### به‌روزرسانی وضعیت سفارش (ادمین)

به‌روزرسانی وضعیت یک سفارش (فقط برای ادمین)

**URL**: `/api/v1/admin/orders/{orderId}/status`

**Method**: `POST`

**پارامترهای مسیر (Path Parameters)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| orderId | عدد | شناسه یکتای سفارش |

**پارامترهای درخواست (Request Body)**:

| پارامتر | نوع | توضیحات |
|---------|-----|---------|
| status | متن | وضعیت جدید (pending, processing, completed, cancelled, rejected) |
| notes | متن | توضیحات مربوط به تغییر وضعیت (اختیاری) |

**پاسخ موفق**:

```json
{
    "success": true,
    "message": "وضعیت سفارش با موفقیت به‌روزرسانی شد",
    "data": {
        "order_id": 123,
        "status": "completed",
        "updated_at": "2023-01-06T16:20:00.000000Z"
    }
}
```

## کدهای خطا

| کد خطا | توضیحات |
|--------|---------|
| 401 | کاربر احراز هویت نشده است |
| 403 | کاربر دسترسی به این منبع را ندارد |
| 404 | منبع درخواستی پیدا نشد |
| 422 | داده‌های ارسالی نامعتبر هستند |

## نکات مهم

1. گفتگوهای سفارشات تکمیل‌شده (status = 'completed') پس از 15 روز به صورت خودکار حذف می‌شوند.
2. در سفارشات تکمیل‌شده، امکان ارسال پیام جدید وجود ندارد.
3. برای استفاده از API سفارشات، کاربر باید احراز هویت شده باشد.
4. فقط ادمین‌ها می‌توانند به API های بخش admin دسترسی داشته باشند.
