# مستندات API های جدید پنل ادمین

در این مستند، API های جدید اضافه شده به پنل ادمین برای مدیریت سفارشات و درخواست‌های تخفیف توضیح داده شده است.

## 1. مدیریت سفارشات

### 1.1. دریافت سفارشات در انتظار تایید

- **URL**: `/api/orders/pending`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `orders.view`
- **پارامترهای Query String**:
  - `page`: شماره صفحه (پیش‌فرض: 1)
- **پاسخ موفق**:
  ```json
  {
    "current_page": 1,
    "data": [
      {
        "order_id": 1,
        "user_id": 2,
        "seller_id": 3,
        "status": "pending",
        "total_amount": "150000.00",
        "created_at": "2024-11-05T10:30:45",
        "user": {
          "user_id": 2,
          "first_name": "علی",
          "last_name": "محمدی"
        },
        "seller": {
          "seller_id": 3,
          "business_name": "فروشگاه دیجیتال آنلاین"
        }
      },
      ...
    ],
    "total": 25,
    "per_page": 10,
    "last_page": 3,
    ...
  }
  ```

### 1.2. به‌روزرسانی وضعیت سفارش

- **URL**: `/api/orders/{id}/status`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `orders.process`
- **Body**:
  ```json
  {
    "status": "approved",
    "reason": "تایید شد" // اختیاری
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "وضعیت سفارش با موفقیت بروزرسانی شد",
    "order": {
      "order_id": 1,
      "status": "approved",
      ...
    }
  }
  ```

### 1.3. دریافت سفارشات در حال انجام

- **URL**: `/api/orders/in-progress`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `orders.view`
- **پارامترهای Query String**:
  - `page`: شماره صفحه (پیش‌فرض: 1)
- **پاسخ موفق**:
  ```json
  {
    "current_page": 1,
    "data": [
      {
        "order_id": 5,
        "user_id": 7,
        "seller_id": 4,
        "status": "in_progress",
        "total_amount": "320000.00",
        "created_at": "2024-11-03T14:20:30",
        "user": {
          "user_id": 7,
          "first_name": "رضا",
          "last_name": "کریمی"
        },
        "seller": {
          "seller_id": 4,
          "business_name": "موبایلیا"
        }
      },
      ...
    ],
    ...
  }
  ```

### 1.4. دریافت گفتگوی سفارش

- **URL**: `/api/orders/{id}/conversation`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `orders.view`
- **پاسخ موفق**:
  ```json
  {
    "order": {
      "order_id": 5,
      "user_id": 7,
      "seller_id": 4,
      "status": "in_progress",
      "total_amount": "320000.00",
      "created_at": "2024-11-03T14:20:30",
      "user": {
        "user_id": 7,
        "first_name": "رضا",
        "last_name": "کریمی",
        "profile_image": "http://example.com/storage/profile_images/user7.jpg"
      },
      "seller": {
        "seller_id": 4,
        "business_name": "موبایلیا",
        "profile_image": "http://example.com/storage/profile_images/seller4.jpg"
      }
    },
    "messages": [
      {
        "message_id": 12,
        "order_id": 5,
        "sender_id": 7,
        "sender_type": "user",
        "message": "سلام، کی سفارش من ارسال می‌شود؟",
        "created_at": "2024-11-04T09:30:15"
      },
      {
        "message_id": 13,
        "order_id": 5,
        "sender_id": 4,
        "sender_type": "seller",
        "message": "سلام، تا فردا ارسال خواهد شد.",
        "created_at": "2024-11-04T10:15:45"
      },
      ...
    ]
  }
  ```

### 1.5. دریافت همه سفارشات

- **URL**: `/api/orders?status=pending`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `orders.view`
- **پارامترهای Query String**:
  - `status`: وضعیت سفارش (اختیاری)
  - `page`: شماره صفحه (پیش‌فرض: 1)
- **پاسخ موفق**: مشابه API های بالا با امکان فیلتر براساس وضعیت

## 2. مدیریت درخواست‌های تخفیف

### 2.1. دریافت درخواست‌های تخفیف در انتظار تایید

- **URL**: `/api/discounts/pending`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `products.manage`
- **پارامترهای Query String**:
  - `page`: شماره صفحه (پیش‌فرض: 1)
- **پاسخ موفق**:
  ```json
  {
    "current_page": 1,
    "data": [
      {
        "discount_id": 1,
        "product_id": 12,
        "seller_id": 4,
        "discount_percentage": "15.00",
        "start_date": "2024-11-10",
        "end_date": "2024-11-20",
        "approval_status": "pending",
        "created_at": "2024-11-05T08:45:30",
        "product": {
          "product_id": 12,
          "name": "هدفون بلوتوث مدل A20",
          "price": "850000.00",
          "image_url": "http://example.com/storage/products/headphone_a20.jpg"
        },
        "seller": {
          "seller_id": 4,
          "business_name": "موبایلیا"
        }
      },
      ...
    ],
    ...
  }
  ```

### 2.2. دریافت جزئیات درخواست تخفیف

- **URL**: `/api/discounts/{id}`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `products.manage`
- **پاسخ موفق**:
  ```json
  {
    "discount_id": 1,
    "product_id": 12,
    "seller_id": 4,
    "discount_percentage": "15.00",
    "start_date": "2024-11-10",
    "end_date": "2024-11-20",
    "approval_status": "pending",
    "created_at": "2024-11-05T08:45:30",
    "product": {
      "product_id": 12,
      "name": "هدفون بلوتوث مدل A20",
      "description": "هدفون بلوتوث با کیفیت صدای فوق‌العاده و باتری با دوام",
      "price": "850000.00",
      "stock": 25,
      "image_url": "http://example.com/storage/products/headphone_a20.jpg",
      "category_id": 3
    },
    "seller": {
      "seller_id": 4,
      "business_name": "موبایلیا",
      "business_email": "info@mobilia.com",
      "phone_number": "09120001122"
    }
  }
  ```

### 2.3. به‌روزرسانی وضعیت درخواست تخفیف

- **URL**: `/api/discounts/{id}/status`
- **Method**: `POST`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `products.manage`
- **Body**:
  ```json
  {
    "status": "approved",
    "reason": "تایید شد" // اختیاری، برای rejected الزامی است
  }
  ```
- **پاسخ موفق**:
  ```json
  {
    "message": "وضعیت تخفیف با موفقیت بروزرسانی شد",
    "discount": {
      "discount_id": 1,
      "approval_status": "approved",
      "is_active": true,
      ...
    }
  }
  ```

### 2.4. دریافت همه درخواست‌های تخفیف

- **URL**: `/api/discounts?status=approved`
- **Method**: `GET`
- **Header**: `Authorization: Bearer YOUR_TOKEN`
- **دسترسی مورد نیاز**: `products.manage`
- **پارامترهای Query String**:
  - `status`: وضعیت درخواست (اختیاری)
  - `page`: شماره صفحه (پیش‌فرض: 1)
- **پاسخ موفق**: مشابه API های بالا با امکان فیلتر براساس وضعیت

## نکات فنی

1. **کدهای وضعیت HTTP**:
   - `200`: درخواست موفق
   - `400`: خطای اعتبارسنجی داده‌ها
   - `401`: عدم احراز هویت
   - `403`: دسترسی غیرمجاز
   - `404`: منبع یافت نشد
   - `500`: خطای سرور

2. **وضعیت‌های سفارش**:
   - `pending`: در انتظار تایید
   - `approved`: تایید شده
   - `rejected`: رد شده
   - `in_progress`: در حال انجام
   - `completed`: تکمیل شده
   - `cancelled`: لغو شده

3. **وضعیت‌های درخواست تخفیف**:
   - `pending`: در انتظار تایید
   - `approved`: تایید شده
   - `rejected`: رد شده

4. **سطوح دسترسی**:
   - `orders.view`: مشاهده سفارشات
   - `orders.process`: پردازش سفارشات
   - `products.manage`: مدیریت محصولات و تخفیف‌ها 
