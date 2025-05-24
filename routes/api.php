<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderAdminController;
use App\Http\Controllers\Api\DiscountRequestController;
use App\Http\Controllers\Api\UserAdminController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\FinancialController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\Api\ServiceProviderController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ServiceProviderDetailsController;
use App\Http\Controllers\Api\ServiceProviderDocumentsController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\PusherTestController;
use App\Http\Controllers\Api\ProductRequestController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\OrderMessageController;
use App\Http\Controllers\Api\AdvertisementRequestController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ChatSettingController;
use App\Http\Controllers\Api\MediaItemController;
use App\Http\Controllers\Api\AdSettingController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\AccountingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// مسیرهای عمومی - بدون نیاز به احراز هویت
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp']);
Route::post('/send-sms-otp', [AuthController::class, 'sendSmsOtp']);

// مسیرهای احراز هویت کاربر با OTP و ایمیل/رمز عبور
Route::post('/auth/otp-send', [AuthController::class, 'requestOtp'])->middleware('otp.limit');
Route::post('/auth/otp-verify', [AuthController::class, 'verifyOtp']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// مسیرهای محافظت شده - نیاز به احراز هویت
Route::middleware('auth:sanctum')->group(function () {
    // مسیرهای مربوط به احراز هویت
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // مسیر پروفایل کاربر
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);

    // مسیرهای مربوط به داشبورد
    Route::get('/dashboard/info', [DashboardController::class, 'getInfo']);
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/tickets', [DashboardController::class, 'getTicketsReport']);
    Route::get('/dashboard/new-users', [DashboardController::class, 'getNewUsers']);
    Route::get('/dashboard/chart', [DashboardController::class, 'getUsersChart']);
    Route::get('/dashboard', [DashboardController::class, 'getDashboard']);

    // مسیرهای مربوط به پروفایل
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

    // مسیرهای مالی و تراکنش‌ها
    Route::prefix('financial')->group(function () {
        // داشبورد مالی
        Route::get('/dashboard', [FinancialController::class, 'getFinancialDashboard']);

        // مدیریت تراکنش‌ها
        Route::get('/transactions', [FinancialController::class, 'getTransactions']);
        Route::get('/transactions/{transactionId}', [FinancialController::class, 'getTransactionDetails']);

        // مدیریت کارت‌های بانکی
        Route::get('/bank-cards', [FinancialController::class, 'getBankCards']);
        Route::post('/bank-cards', [FinancialController::class, 'addBankCard']);
        Route::put('/bank-cards/{cardId}', [FinancialController::class, 'updateBankCard']);
        Route::delete('/bank-cards/{cardId}', [FinancialController::class, 'deleteBankCard']);

        // مدیریت کارت‌های هدیه
        Route::get('/gift-cards', [FinancialController::class, 'getGiftCards']);
        Route::post('/gift-cards/use', [FinancialController::class, 'useGiftCard']);

        // ایجاد کارت هدیه (فقط برای ادمین)
        Route::post('/gift-cards/create', [FinancialController::class, 'createGiftCard']);
    });

    // مسیرهای حسابداری و مدیریت تراکنش‌ها
    Route::prefix('accounting')->group(function () {
        // آمار و اطلاعات کلی
        Route::get('/summary', [AccountingController::class, 'summary']);
        Route::get('/revenue-chart', [AccountingController::class, 'revenueChart']);
        
        // مدیریت تراکنش‌ها
        Route::get('/transactions', [AccountingController::class, 'index']);
        Route::get('/transactions/{id}', [AccountingController::class, 'show']);
        Route::patch('/transactions/{id}/approve', [AccountingController::class, 'approve']);
        Route::patch('/transactions/{id}/reject', [AccountingController::class, 'reject']);
        Route::patch('/transactions/{id}/settle', [AccountingController::class, 'settle']);
        
        // درخواست برداشت
        Route::post('/user-withdrawal', [AccountingController::class, 'createUserWithdrawal']);
        Route::post('/provider-withdrawal', [AccountingController::class, 'createProviderWithdrawal']);
    });

    // مسیرهای مربوط به جستجو
    Route::get('/search', [SearchController::class, 'search']);

    // مسیرهای مربوط به مدیریت درخواست‌ها
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests/{id}/process', [RequestController::class, 'process']);
    Route::delete('/requests/{id}', [RequestController::class, 'destroy']);

    // مسیرهای مربوط به مدیریت سفارشات
    Route::get('/orders', [OrderAdminController::class, 'index']);
    Route::post('/orders', [OrderAdminController::class, 'store']);
    Route::get('/orders/{orderId}/details', [OrderAdminController::class, 'show']);
    Route::put('/orders/{orderId}/approve', [OrderAdminController::class, 'approve']);
    Route::get('/export-orders', [OrderAdminController::class, 'exportOrders']);

    // مسیرهای مربوط به درخواست‌های تخفیف
    Route::get('/discount-requests', [DiscountRequestController::class, 'index']);
    Route::get('/discount-requests/{requestId}/details', [DiscountRequestController::class, 'show']);
    Route::put('/discount-requests/{requestId}/approve', [DiscountRequestController::class, 'updateStatus']);
    Route::get('/discount-stats', [DiscountRequestController::class, 'discountStats']);
    Route::get('/export-discounts', [DiscountRequestController::class, 'exportDiscounts']);

    // مسیرهای مربوط به درخواست‌های محصول
    Route::get('/product-requests', [ProductRequestController::class, 'index']);
    Route::get('/product-requests/pending', [ProductRequestController::class, 'pendingRequests']);
    Route::get('/product-requests/{id}', [ProductRequestController::class, 'show']);
    Route::put('/product-requests/{id}/status', [ProductRequestController::class, 'updateStatus']);
    Route::get('/product-requests/{id}/download', [ProductRequestController::class, 'downloadFile']);
    Route::get('/product-requests/export', [ProductRequestController::class, 'exportRequests']);

    // مسیرهای مربوط به درخواست‌های تبلیغات
    Route::get('/ad-requests', [AdvertisementRequestController::class, 'index']);
    Route::get('/ad-requests/pending', [AdvertisementRequestController::class, 'pendingRequests']);
    Route::get('/ad-requests/{id}', [AdvertisementRequestController::class, 'show']);
    Route::put('/ad-requests/{id}/approve', [AdvertisementRequestController::class, 'updateStatus']);
    Route::get('/ad-requests/export', [AdvertisementRequestController::class, 'exportRequests']);
    
    // مسیر دانلود گزارش ترکیبی
    Route::get('/export-requests', [ProductRequestController::class, 'exportAllRequests']);

    // مسیرهای مربوط به مدیریت کاربران
    Route::get('/admin/users/stats', [UserAdminController::class, 'getStats']);
    Route::get('/admin/users', [UserAdminController::class, 'index']);
    Route::get('/admin/users/{id}', [UserAdminController::class, 'show']);
    Route::post('/admin/users', [UserAdminController::class, 'store']);
    Route::put('/admin/users/{id}', [UserAdminController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserAdminController::class, 'destroy']);
    Route::post('/admin/users/{id}/message', [UserAdminController::class, 'sendMessage']);
    Route::get('/admin/users/export', [UserAdminController::class, 'export']);
    Route::post('/admin/users/{id}/subscription', [UserAdminController::class, 'manageSubscription']);
    Route::get('/admin/users/{id}/subscriptions', [UserAdminController::class, 'getUserSubscriptions']);
    Route::get('/admin/users/{userId}/orders/{orderId}', [UserAdminController::class, 'getOrderDetails']);
    Route::get('/admin/subscription-plans', [UserAdminController::class, 'getSubscriptionPlans']);
    Route::post('/admin/users/unlock-account', [AuthController::class, 'unlockAccount']);

    // روت‌های جدید مربوط به اطلاعات کاربر و تایید اطلاعات
    Route::prefix('v1')->group(function () {
        // مدیریت اطلاعات کاربر
        Route::get('/user/profile', [UserController::class, 'getCurrentUser']);
        Route::post('/user/profile', [UserController::class, 'updateProfileInfo']);

        // تایید اطلاعات کاربر (فقط برای ادمین)
        Route::post('/admin/users/{userId}/verify', [UserController::class, 'verifyUserInfo']);
        Route::get('/admin/users', [UserController::class, 'getUsers']);
        Route::get('/admin/users/{userId}', [UserController::class, 'getUserById']);

        // مدیریت سفارشات کاربر
        Route::get('/user/orders', [OrderController::class, 'getUserOrders']);
        Route::get('/user/orders/{orderId}', [OrderController::class, 'getOrderDetails']);
        Route::get('/user/orders/{orderId}/conversation', [OrderController::class, 'getOrderConversation']);
        Route::post('/user/orders/{orderId}/message', [OrderController::class, 'sendMessage']);

        // مدیریت همه سفارشات (فقط برای ادمین)
        Route::get('/admin/orders', [OrderController::class, 'getAllOrders']);
        Route::post('/admin/orders/{orderId}/status', [OrderController::class, 'updateOrderStatus']);

        // مدیریت خدمات‌دهندگان
        Route::prefix('service-providers')->group(function () {
            Route::get('/', [ServiceProviderController::class, 'index']);
            Route::post('/', [ServiceProviderController::class, 'store']);
            Route::post('/register', [ServiceProviderController::class, 'register']);
            Route::get('/export', [ExportController::class, 'exportToExcel']);
            Route::get('/export-registration', [ExportController::class, 'exportRegistration']);
            Route::get('/{id}', [ServiceProviderController::class, 'show']);
            Route::put('/{id}', [ServiceProviderController::class, 'update']);
            Route::delete('/{id}', [ServiceProviderController::class, 'destroy']);
            Route::put('/{id}/status', [ServiceProviderController::class, 'updateStatus']);
            Route::post('/{id}/message', [MessageController::class, 'send']);
            Route::get('/documents/{id}/download', [ServiceProviderController::class, 'downloadDocument']);
            Route::delete('/documents/{id}', [ServiceProviderController::class, 'deleteDocument']);
            
            // روت‌های جزئیات
            Route::get('/{id}/details', [ServiceProviderDetailsController::class, 'details']);
            Route::put('/{id}/toggle-activity/{activityId}', [ServiceProviderDetailsController::class, 'toggleActivity']);
            Route::get('/{id}/activity-chart', [ServiceProviderDetailsController::class, 'activityChart']);
            Route::post('/{id}/rate', [ServiceProviderDetailsController::class, 'rate']);
            Route::get('/{id}/orders', [ServiceProviderDetailsController::class, 'orders']);
            Route::get('/{id}/export-details', [ServiceProviderDetailsController::class, 'exportDetails']);
            
            // روت‌های مبتنی بر نوع
            Route::get('/type/{type}', [ServiceProviderDetailsController::class, 'getByType']);
            Route::get('/connectyar/{id}/details', [ServiceProviderDetailsController::class, 'connectyarDetails']);
            Route::get('/{id}/performance', [ServiceProviderDetailsController::class, 'performanceStats']);
        });
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{notificationId}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });

    // Login Settings route
    Route::prefix('account-security')->group(function () {
        Route::get('/login-settings', [ProfileController::class, 'getLoginSettings']);
        Route::post('/login-settings', [ProfileController::class, 'updateLoginSettings']);
        Route::get('/devices', [ProfileController::class, 'getLoginDevices']);
        Route::delete('/devices/{deviceId}', [ProfileController::class, 'revokeDevice']);
        Route::delete('/devices', [ProfileController::class, 'revokeAllDevices']);
    });

    // مدیریت مدارک خدمات‌دهندگان
    Route::get('/service-providers/{id}/documents', [ServiceProviderDocumentsController::class, 'index']);
    
    // تایید یا رد مدرک
    Route::put('/service-providers/{id}/documents/{documentId}/status', [ServiceProviderDocumentsController::class, 'updateStatus']);
    
    // دانلود مدرک
    Route::get('/service-providers/{id}/documents/{documentId}/download', [ServiceProviderDocumentsController::class, 'download']);
    
    // فیلتر خدمات‌دهندگان بر اساس وضعیت
    Route::get('/service-providers/documents/filter', [ServiceProviderDocumentsController::class, 'filterProviders']);
    
    // دانلود گزارش مدارک
    Route::get('/service-providers/documents/export', [ServiceProviderDocumentsController::class, 'exportDocuments']);
    
    // آپلود مدرک جدید (برای خدمات‌دهنده)
    Route::post('/service-providers/{id}/documents/upload', [ServiceProviderDocumentsController::class, 'uploadDocument']);

    // دریافت لیست خدمات‌دهندگان با فیلتر (نوع، شهر، دسته‌بندی)
    Route::get('/service-providers/filtered', [ServiceProviderController::class, 'getFilteredProviders']);

    // مسیرهای مدیریت محصولات
    Route::prefix('products')->group(function () {
        // مسیرهای عمومی (بدون نیاز به احراز هویت)
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        
        // مسیرهای نیازمند احراز هویت
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        
        // مسیرهای مخصوص خدمات‌دهندگان
        Route::get('/service-provider/{serviceProviderId}', [ProductController::class, 'serviceProviderProducts']);
        
        // مسیرهای مخصوص ادمین
        Route::middleware('admin')->group(function () {
            Route::get('/pending/list', [ProductController::class, 'pendingProducts']);
            Route::put('/{id}/approval', [ProductController::class, 'updateApprovalStatus']);
        });
    });

    // مسیرهای خدمات‌دهندگان مرتبط با محصولات
    Route::get('service-providers/{id}/products', [ProductController::class, 'serviceProviderProducts']);
    
    // مسیرهای مدیریت تیکت‌ها
    Route::prefix('tickets')->group(function () {
        // نمایش لیست تیکت‌ها
        Route::get('/', [TicketController::class, 'index']);
        
        // ایجاد تیکت جدید
        Route::post('/', [TicketController::class, 'store']);
        
        // نمایش جزئیات تیکت
        Route::get('/{ticketId}', [TicketController::class, 'show']);
        
        // پاسخ به تیکت
        Route::post('/{ticketId}/reply', [TicketController::class, 'reply']);
        
        // تغییر وضعیت تیکت
        Route::put('/{ticketId}/status', [TicketController::class, 'updateStatus']);
        
        // دانلود پیوست تیکت
        Route::get('/{ticketId}/download-attachment/{messageId}', [TicketController::class, 'downloadAttachment']);
    });

    // سیستم استرداد وجه
    Route::prefix('refunds')->group(function () {
        Route::post('/request', [RefundController::class, 'requestRefund']);
        Route::post('/process', [RefundController::class, 'processRefund']);
        Route::get('/user', [RefundController::class, 'getUserRefunds']);
        Route::get('/admin/all', [RefundController::class, 'getAllRefunds']);
    });

    // مدیریت پیام‌های سفارشات
    Route::prefix('orders/messages')->group(function () {
        Route::get('/order/{orderId}', [OrderMessageController::class, 'getConversation']);
        Route::post('/order/{orderId}', [OrderMessageController::class, 'sendMessage']);
        Route::get('/{messageId}/download-file', [OrderMessageController::class, 'downloadFile']);
        Route::put('/{messageId}/mark-as-read', [OrderMessageController::class, 'markAsRead']);
        Route::get('/unread-counts', [OrderMessageController::class, 'getUnreadCounts']);
    });

    // مسیرهای مربوط به مدیریت خدمات
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{serviceId}', [ServiceController::class, 'update']);
        Route::put('/{serviceId}/toggle-status', [ServiceController::class, 'toggleStatus']);
        Route::get('/{serviceId}/active-providers', [ServiceController::class, 'getActiveServiceProviders']);
        Route::get('/export', [ExportController::class, 'exportServices']);
    });

    // مسیرهای مربوط به نظرات خدمات‌دهندگان
    Route::prefix('reviews')->group(function () {
        // مسیرهای عمومی
        Route::get('/service-provider/{serviceProviderId}', [ReviewController::class, 'getServiceProviderReviews']);
        
        // مسیرهای نیازمند احراز هویت کاربر
        Route::middleware('auth:api')->group(function () {
            Route::post('/submit', [ReviewController::class, 'submitReview']);
            Route::get('/can-submit', [ReviewController::class, 'canSubmitReview']);
        });
        
        // مسیرهای مخصوص ادمین
        Route::middleware('auth:api')->group(function () {
            Route::get('/admin/list', [ReviewController::class, 'listReviews']);
            Route::get('/admin/{reviewId}', [ReviewController::class, 'getReviewDetails']);
            Route::patch('/admin/{reviewId}/approve', [ReviewController::class, 'approveReview']);
            Route::patch('/admin/{reviewId}/reject', [ReviewController::class, 'rejectReview']);
        });
    });

    // مسیرهای مربوط به تنظیمات چت و کنترل اسپم
    Route::prefix('settings')->group(function () {
        // مسیرهای مخصوص ادمین
        Route::middleware('auth:api')->group(function () {
            Route::get('/chat-filters', [ChatSettingController::class, 'getSettings']);
            Route::put('/chat-filters', [ChatSettingController::class, 'updateSettings']);
            Route::post('/chat-filters/reset', [ChatSettingController::class, 'resetSettings']);
            Route::post('/chat-filters/initialize', [ChatSettingController::class, 'initializeSettings']);
        });
        
        // مسیر بررسی پیام برای همه کاربران احراز هویت شده
        Route::middleware('auth:api')->group(function () {
            Route::post('/check-message', [ChatSettingController::class, 'checkMessage']);
        });
    });

    // مسیرهای مربوط به مدیریت بنر و اسلایدر
    Route::prefix('media-items')->group(function () {
        // دریافت فهرست آیتم‌ها
        Route::get('/', [MediaItemController::class, 'index']);
        
        // مسیرهای محافظت شده با احراز هویت ادمین
        Route::middleware('auth:api')->group(function () {
            // دریافت جزئیات یک آیتم
            Route::get('/{id}', [MediaItemController::class, 'show']);
            
            // ایجاد آیتم جدید
            Route::post('/', [MediaItemController::class, 'store']);
            
            // به‌روزرسانی آیتم
            Route::put('/{id}', [MediaItemController::class, 'update']);
            
            // حذف آیتم
            Route::delete('/{id}', [MediaItemController::class, 'destroy']);
            
            // تغییر وضعیت فعال/غیرفعال
            Route::patch('/{id}/status', [MediaItemController::class, 'toggleStatus']);
        });
    });

    // مسیرهای مربوط به تنظیمات تبلیغات
    Route::prefix('ad-settings')->group(function () {
        // دریافت فهرست تنظیمات
        Route::get('/', [AdSettingController::class, 'index']);
        
        // مسیرهای محافظت شده با احراز هویت ادمین
        Route::middleware('auth:api')->group(function () {
            // دریافت جزئیات یک تنظیم
            Route::get('/{id}', [AdSettingController::class, 'show']);
            
            // ایجاد تنظیم جدید
            Route::post('/', [AdSettingController::class, 'store']);
            
            // به‌روزرسانی تنظیم
            Route::put('/{id}', [AdSettingController::class, 'update']);
            
            // حذف تنظیم
            Route::delete('/{id}', [AdSettingController::class, 'destroy']);
            
            // تغییر وضعیت فعال/غیرفعال
            Route::patch('/{id}/status', [AdSettingController::class, 'toggleStatus']);
        });
    });

    // مسیرهای مدیریت اطلاعیه‌ها و قوانین
    Route::prefix('notices')->group(function () {
        // مسیرهای عمومی (فقط برای اطلاعیه‌های منتشر شده)
        Route::get('/', [NoticeController::class, 'index']);
        Route::get('/{id}', [NoticeController::class, 'show']);
        Route::get('/unread/count', [NoticeController::class, 'getUnreadCount']);
        
        // مسیرهای مدیریتی (فقط برای ادمین و مدیر محتوا)
        Route::post('/', [NoticeController::class, 'store']);
        Route::put('/{id}', [NoticeController::class, 'update']);
        Route::patch('/{id}/publish', [NoticeController::class, 'publish']);
        Route::patch('/{id}/archive', [NoticeController::class, 'archive']);
        Route::delete('/{id}', [NoticeController::class, 'destroy']);
    });

    // مسیرهای مربوط به سبد خرید
    Route::prefix('cart')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\CartController::class, 'index']);
        Route::post('/add-item', [App\Http\Controllers\Api\CartController::class, 'addItem']);
        Route::put('/update-item/{itemId}', [App\Http\Controllers\Api\CartController::class, 'updateItem']);
        Route::delete('/remove-item/{itemId}', [App\Http\Controllers\Api\CartController::class, 'removeItem']);
        Route::post('/clear', [App\Http\Controllers\Api\CartController::class, 'clear']);
        Route::post('/apply-discount', [App\Http\Controllers\Api\CartController::class, 'applyDiscount']);
        Route::post('/remove-discount', [App\Http\Controllers\Api\CartController::class, 'removeDiscount']);
    });

    // مسیرهای مربوط به تسویه حساب و پرداخت
    Route::prefix('checkout')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\CheckoutController::class, 'checkout']);
        Route::post('/process-payment', [App\Http\Controllers\Api\CheckoutController::class, 'processPayment']);
    });

    // مسیرهای مربوط به پرداخت
    Route::prefix('payments')->group(function () {
        // مسیرهای عمومی (بدون نیاز به احراز هویت)
        Route::get('/verify/{paymentId}', [App\Http\Controllers\Api\CheckoutController::class, 'verifyPayment']);
        Route::get('/info/{trackingCode}', [App\Http\Controllers\Api\CheckoutController::class, 'getPaymentInfo']);
        
        // مسیرهای نیازمند احراز هویت
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user-payments', [App\Http\Controllers\Api\PaymentController::class, 'getUserPayments']);
        });
    });

    // مسیرهای مربوط به سفارشات کاربر
    Route::prefix('user-orders')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\OrderController::class, 'getUserOrders']);
        Route::get('/{orderId}', [App\Http\Controllers\Api\OrderController::class, 'getUserOrderDetails']);
        Route::get('/download-file/{fileId}', [App\Http\Controllers\Api\OrderController::class, 'downloadFile']);
    });

    // مسیرهای مربوط به مدیریت سفارشات (مدیر)
    Route::prefix('admin/orders')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/', [App\Http\Controllers\Api\OrderController::class, 'getAllOrders']);
        Route::get('/{orderId}', [App\Http\Controllers\Api\OrderController::class, 'getAdminOrderDetails']);
        Route::post('/{orderId}/approve', [App\Http\Controllers\Api\OrderController::class, 'approveOrder']);
        Route::post('/{orderId}/reject', [App\Http\Controllers\Api\OrderController::class, 'rejectOrder']);
    });

    // مسیرهای مربوط به سفارشات فروشنده
    Route::prefix('seller/orders')->middleware(['auth:sanctum', 'seller'])->group(function () {
        Route::get('/', [App\Http\Controllers\Api\OrderController::class, 'getSellerOrders']);
        Route::get('/{orderItemId}', [App\Http\Controllers\Api\OrderController::class, 'getSellerOrderDetails']);
        Route::post('/{orderItemId}/upload-file', [App\Http\Controllers\Api\OrderController::class, 'uploadFile']);
    });
});

// مسیرهای مربوط به ارائه‌دهندگان خدمات
Route::prefix('provider')->group(function () {
    // مسیرهای عمومی (بدون نیاز به احراز هویت)
    Route::post('/request-otp', [App\Http\Controllers\Api\ProviderAuthController::class, 'requestOtp'])->middleware('otp.limit');
    Route::post('/verify-otp', [App\Http\Controllers\Api\ProviderAuthController::class, 'verifyOtp']);
    Route::post('/register', [App\Http\Controllers\Api\ProviderAuthController::class, 'register']);

    // مسیرهای محافظت شده (نیاز به احراز هویت)
    Route::middleware(['auth:sanctum', 'ability:provider'])->group(function () {
        // پروفایل و داشبورد
        Route::get('/profile', [App\Http\Controllers\Api\ProviderAuthController::class, 'profile']);
        Route::get('/dashboard', [App\Http\Controllers\Api\ProviderDashboardController::class, 'index']);
        
        // مدیریت سفارشات
        Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'getProviderOrders']);
        Route::get('/orders/{orderId}', [App\Http\Controllers\Api\OrderController::class, 'getProviderOrderDetails']);
        Route::post('/orders/{orderId}/status', [App\Http\Controllers\Api\OrderController::class, 'updateProviderOrderStatus']);
        Route::post('/orders/{orderId}/message', [App\Http\Controllers\Api\OrderMessageController::class, 'sendProviderMessage']);

        // مدیریت مالی
        Route::get('/financial/stats', [App\Http\Controllers\Api\FinancialController::class, 'getProviderFinancialStats']);
        Route::get('/financial/transactions', [App\Http\Controllers\Api\FinancialController::class, 'getProviderTransactions']);
        Route::post('/financial/withdraw', [App\Http\Controllers\Api\FinancialController::class, 'requestProviderWithdraw']);
    });
});

// مسیرهای مدیریت بنرها و اسلایدرها (فقط برای کاربران ادمین)
Route::prefix('media-items')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [MediaItemController::class, 'index']);
    Route::get('/{id}', [MediaItemController::class, 'show']);
    Route::post('/', [MediaItemController::class, 'store']);
    Route::post('/{id}/update', [MediaItemController::class, 'update']);
    Route::delete('/{id}', [MediaItemController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [MediaItemController::class, 'toggleStatus']);
    Route::get('/position/{position}', [MediaItemController::class, 'getByPosition']);
});

// مسیرهای عمومی بنرها که نیاز به احراز هویت ندارند
Route::prefix('banners')->group(function () {
    Route::get('/position/{position}', [MediaItemController::class, 'getByPosition']);
});

// مسیرهای عمومی اسلایدرها که نیاز به احراز هویت ندارند
Route::prefix('sliders')->group(function () {
    Route::get('/', [MediaItemController::class, 'getSliders']);
    Route::get('/position/{position}', [MediaItemController::class, 'getByPosition']);
});

// مسیرهای مربوط به بخش‌های "بزودی"
Route::prefix('coming-soon')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ComingSoonController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Api\ComingSoonController::class, 'show']);
    Route::post('/{id}/track', [App\Http\Controllers\Api\ComingSoonController::class, 'trackVisit']);
});

// مسیرهای مربوط به کدهای تخفیف
Route::prefix('discount-codes')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\DiscountCodeController::class, 'getActiveCodes']);
    Route::get('/banner', [App\Http\Controllers\Api\DiscountCodeController::class, 'getActiveBannerCode']);
    Route::post('/validate', [App\Http\Controllers\Api\DiscountCodeController::class, 'validateCode']);
    Route::post('/track', [App\Http\Controllers\Api\DiscountCodeController::class, 'trackCopy']);
});
