<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * دریافت اطلاعات نهایی سبد خرید برای پرداخت
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        $user = $request->user();
        
        // دریافت سبد خرید کاربر
        $cart = $this->getCart($user->id);
        
        if (!$cart || $cart->total_items == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'سبد خرید شما خالی است'
            ], 400);
        }
        
        // بارگذاری رابطه‌ها
        $cart->load(['items.product']);
        
        // بررسی موجودی کیف پول برای نمایش در گزینه‌های پرداخت
        $wallet = Wallet::where('user_id', $user->id)->first();
        $hasEnoughBalance = false;
        
        if ($wallet) {
            $hasEnoughBalance = $wallet->hasEnoughBalance($cart->final_price);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'cart' => $cart,
                'items' => $cart->items,
                'total_items' => $cart->total_items,
                'total_price' => $cart->total_price,
                'discount_amount' => $cart->discount_amount,
                'final_price' => $cart->final_price,
                'payment_options' => [
                    [
                        'id' => 'online',
                        'title' => 'پرداخت آنلاین',
                        'description' => 'پرداخت از طریق درگاه بانکی',
                        'is_available' => true,
                    ],
                    [
                        'id' => 'wallet',
                        'title' => 'پرداخت از کیف پول',
                        'description' => 'پرداخت از موجودی کیف پول',
                        'is_available' => $hasEnoughBalance,
                        'wallet_balance' => $wallet ? $wallet->balance : 0,
                    ],
                    [
                        'id' => 'credit',
                        'title' => 'پرداخت اعتباری',
                        'description' => 'پرداخت از طریق اعتبار',
                        'is_available' => false,
                        'message' => 'این روش پرداخت در حال حاضر غیرفعال است',
                    ],
                ],
            ]
        ]);
    }

    /**
     * ایجاد سفارش و انتقال به درگاه پرداخت
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:online,wallet',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $paymentMethod = $request->input('payment_method');
        $notes = $request->input('notes');
        
        // دریافت سبد خرید کاربر
        $cart = $this->getCart($user->id);
        
        if (!$cart || $cart->total_items == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'سبد خرید شما خالی است'
            ], 400);
        }
        
        // بارگذاری آیتم‌های سبد خرید
        $cart->load(['items.product']);
        
        DB::beginTransaction();
        
        try {
            // ایجاد سفارش جدید
            $order = new Order([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'total_price' => $cart->total_price,
                'status' => Order::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'payment_status' => Payment::STATUS_PENDING,
                'discount_code' => $cart->discount_code,
                'discount_amount' => $cart->discount_amount,
                'final_price' => $cart->final_price,
                'notes' => $notes,
            ]);
            
            $order->save();
            
            // ایجاد آیتم‌های سفارش
            foreach ($cart->items as $cartItem) {
                $orderItem = new OrderItem([
                    'product_id' => $cartItem->product_id,
                    'seller_id' => $cartItem->product->user_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total_price' => $cartItem->total_price,
                    'options' => $cartItem->options,
                    'status' => Order::STATUS_PENDING,
                ]);
                
                $order->items()->save($orderItem);
            }
            
            // انتخاب روش پرداخت
            if ($paymentMethod === 'wallet') {
                // پرداخت از کیف پول
                return $this->processWalletPayment($order, $user, $cart);
            } else {
                // پرداخت آنلاین
                return $this->processOnlinePayment($order, $user, $cart);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در ایجاد سفارش', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد سفارش: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * پردازش پرداخت از طریق کیف پول
     *
     * @param Order $order
     * @param User $user
     * @param Cart $cart
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processWalletPayment($order, $user, $cart)
    {
        // دریافت کیف پول کاربر
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'کیف پول شما موجود نیست'
            ], 400);
        }
        
        // بررسی موجودی کیف پول
        if (!$wallet->hasEnoughBalance($order->final_price)) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'موجودی کیف پول شما کافی نیست'
            ], 400);
        }
        
        try {
            // ایجاد رکورد پرداخت
            $payment = new Payment([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $order->final_price,
                'method' => Payment::METHOD_WALLET,
                'status' => Payment::STATUS_PAID,
                'tracking_code' => Payment::generateTrackingCode(),
                'ip_address' => $user->ip(),
                'description' => 'پرداخت از کیف پول برای سفارش #' . $order->order_number,
            ]);
            
            $payment->save();
            
            // بروزرسانی وضعیت سفارش
            $order->payment_status = Payment::STATUS_PAID;
            $order->status = Order::STATUS_PAID;
            $order->payment_id = $payment->id;
            $order->save();
            
            // برداشت از کیف پول
            $wallet->spend($order->final_price, $order->id, 'پرداخت سفارش #' . $order->order_number);
            
            // خالی کردن سبد خرید
            $this->clearCart($cart);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'پرداخت با موفقیت انجام شد. سفارش شما به زودی بررسی خواهد شد.',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'tracking_code' => $payment->tracking_code,
                    'amount' => $order->final_price,
                    'payment_method' => 'کیف پول',
                    'status' => 'پرداخت شده',
                    'date' => $payment->created_at,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در پرداخت از کیف پول', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در پرداخت از کیف پول: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * پردازش پرداخت آنلاین
     *
     * @param Order $order
     * @param User $user
     * @param Cart $cart
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processOnlinePayment($order, $user, $cart)
    {
        try {
            // ایجاد رکورد پرداخت
            $payment = new Payment([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $order->final_price,
                'method' => Payment::METHOD_ONLINE,
                'status' => Payment::STATUS_PENDING,
                'tracking_code' => Payment::generateTrackingCode(),
                'ip_address' => $user->ip(),
                'description' => 'پرداخت آنلاین برای سفارش #' . $order->order_number,
            ]);
            
            $payment->save();
            
            // بروزرسانی سفارش
            $order->payment_id = $payment->id;
            $order->save();
            
            // در اینجا باید کد اتصال به درگاه پرداخت را قرار دهید
            // برای مثال به جای کد واقعی، فرض می‌کنیم یک URL بازگشت از درگاه داریم
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'در حال انتقال به درگاه پرداخت...',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'tracking_code' => $payment->tracking_code,
                    'amount' => $order->final_price,
                    'payment_url' => url('/api/payments/gateway/' . $payment->id),
                    'callback_url' => url('/api/payments/verify/' . $payment->id),
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در پرداخت آنلاین', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد تراکنش پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * تایید پرداخت آنلاین (callback از درگاه پرداخت)
     *
     * @param Request $request
     * @param int $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request, $paymentId)
    {
        // در اینجا پارامترهای برگشتی از درگاه پرداخت را دریافت می‌کنید
        // و صحت پرداخت را بررسی می‌کنید
        
        $payment = Payment::findOrFail($paymentId);
        $order = $payment->order;
        
        DB::beginTransaction();
        
        try {
            // فرض می‌کنیم پرداخت موفق بوده است
            $paymentSuccessful = true; // این مقدار باید بر اساس بررسی پارامترهای درگاه تعیین شود
            
            if ($paymentSuccessful) {
                // بروزرسانی وضعیت پرداخت
                $payment->status = Payment::STATUS_PAID;
                $payment->transaction_id = $request->input('transaction_id', ''); // شماره تراکنش از درگاه
                $payment->reference_id = $request->input('reference_id', ''); // شماره مرجع از درگاه
                $payment->card_number = $request->input('card_number', ''); // شماره کارت از درگاه
                $payment->gateway_response = [
                    'status' => 'success',
                    'transaction_id' => $request->input('transaction_id', ''),
                    'reference_id' => $request->input('reference_id', ''),
                    'card_number' => $request->input('card_number', ''),
                    'response_code' => $request->input('response_code', ''),
                ];
                $payment->save();
                
                // بروزرسانی وضعیت سفارش
                $order->payment_status = Payment::STATUS_PAID;
                $order->status = Order::STATUS_PAID;
                $order->save();
                
                // خالی کردن سبد خرید
                $cart = Cart::where('user_id', $order->user_id)->first();
                if ($cart) {
                    $this->clearCart($cart);
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'پرداخت با موفقیت انجام شد. سفارش شما به زودی بررسی خواهد شد.',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tracking_code' => $payment->tracking_code,
                        'amount' => $order->final_price,
                        'payment_method' => 'آنلاین',
                        'status' => 'پرداخت شده',
                        'date' => $payment->updated_at,
                    ]
                ]);
                
            } else {
                // پرداخت ناموفق
                $payment->status = Payment::STATUS_FAILED;
                $payment->gateway_response = [
                    'status' => 'failed',
                    'response_code' => $request->input('response_code', ''),
                    'error_message' => $request->input('error_message', ''),
                ];
                $payment->save();
                
                DB::commit();
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'پرداخت ناموفق بود. لطفاً مجدداً تلاش کنید.',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tracking_code' => $payment->tracking_code,
                        'payment_status' => 'ناموفق',
                    ]
                ], 400);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در تایید پرداخت', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تایید پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * دریافت اطلاعات پرداخت
     *
     * @param Request $request
     * @param string $trackingCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentInfo(Request $request, $trackingCode)
    {
        $payment = Payment::where('tracking_code', $trackingCode)->first();
        
        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات پرداخت یافت نشد'
            ], 404);
        }
        
        $payment->load(['order']);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $payment->order->order_number,
                'tracking_code' => $payment->tracking_code,
                'amount' => $payment->amount,
                'payment_method' => $payment->method === Payment::METHOD_ONLINE ? 'آنلاین' : 'کیف پول',
                'status' => $payment->status === Payment::STATUS_PAID ? 'پرداخت شده' : 'در انتظار پرداخت',
                'date' => $payment->created_at,
                'card_number' => $payment->card_number,
                'transaction_id' => $payment->transaction_id,
                'reference_id' => $payment->reference_id,
            ]
        ]);
    }
    
    /**
     * دریافت سبد خرید کاربر
     *
     * @param int $userId
     * @return Cart|null
     */
    protected function getCart($userId)
    {
        return Cart::where('user_id', $userId)->first();
    }
    
    /**
     * خالی کردن سبد خرید
     *
     * @param Cart $cart
     * @return void
     */
    protected function clearCart($cart)
    {
        if ($cart) {
            $cart->items()->delete();
            $cart->total_items = 0;
            $cart->total_price = 0;
            $cart->discount_amount = 0;
            $cart->final_price = 0;
            $cart->discount_code = null;
            $cart->save();
        }
    }
} 