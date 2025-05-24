<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * دریافت سبد خرید کاربر
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // دریافت سبد خرید کاربر یا ایجاد سبد جدید
        $cart = $this->getOrCreateCart($user->id);
        
        // بارگذاری رابطه‌ها
        $cart->load(['items.product']);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'cart' => $cart,
                'items' => $cart->items,
                'total_items' => $cart->total_items,
                'total_price' => $cart->total_price,
                'discount_amount' => $cart->discount_amount,
                'final_price' => $cart->final_price,
            ]
        ]);
    }

    /**
     * افزودن محصول به سبد خرید
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10',
            'options' => 'nullable|array',
        ]);

        $user = $request->user();
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $options = $request->input('options', []);

        // دریافت اطلاعات محصول
        $product = Product::findOrFail($productId);
        
        // بررسی وضعیت محصول
        if (!$product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'این محصول در حال حاضر قابل خرید نیست'
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // دریافت سبد خرید کاربر یا ایجاد سبد جدید
            $cart = $this->getOrCreateCart($user->id);
            
            // بررسی وجود محصول در سبد خرید
            $cartItem = $cart->items()->where('product_id', $productId)->first();
            
            if ($cartItem) {
                // به‌روزرسانی تعداد
                $cartItem->quantity += $quantity;
                $cartItem->price = $product->current_price;
                $cartItem->total_price = $cartItem->price * $cartItem->quantity;
                $cartItem->options = $options;
                $cartItem->save();
            } else {
                // افزودن آیتم جدید به سبد خرید
                $cartItem = new CartItem([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->current_price,
                    'total_price' => $product->current_price * $quantity,
                    'options' => $options,
                ]);
                
                $cart->items()->save($cartItem);
            }
            
            // محاسبه مجدد جمع سبد خرید
            $cart->calculateTotals();
            
            DB::commit();
            
            // بارگذاری مجدد اطلاعات سبد خرید
            $cart->load(['items.product']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'محصول با موفقیت به سبد خرید اضافه شد',
                'data' => [
                    'cart' => $cart,
                    'items' => $cart->items,
                    'total_items' => $cart->total_items,
                    'total_price' => $cart->total_price,
                    'discount_amount' => $cart->discount_amount,
                    'final_price' => $cart->final_price,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در افزودن محصول به سبد خرید: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * به‌روزرسانی تعداد محصول در سبد خرید
     *
     * @param Request $request
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
            'options' => 'nullable|array',
        ]);

        $user = $request->user();
        $quantity = $request->input('quantity');
        $options = $request->input('options');
        
        // دریافت سبد خرید کاربر
        $cart = $this->getOrCreateCart($user->id);
        
        // بررسی وجود آیتم در سبد خرید
        $cartItem = $cart->items()->where('id', $itemId)->first();
        
        if (!$cartItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'آیتم مورد نظر در سبد خرید شما وجود ندارد'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // به‌روزرسانی تعداد و گزینه‌های محصول
            $cartItem->quantity = $quantity;
            
            if ($options !== null) {
                $cartItem->options = $options;
            }
            
            $cartItem->total_price = $cartItem->price * $quantity;
            $cartItem->save();
            
            // محاسبه مجدد جمع سبد خرید
            $cart->calculateTotals();
            
            DB::commit();
            
            // بارگذاری مجدد اطلاعات سبد خرید
            $cart->load(['items.product']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'سبد خرید با موفقیت به‌روزرسانی شد',
                'data' => [
                    'cart' => $cart,
                    'items' => $cart->items,
                    'total_items' => $cart->total_items,
                    'total_price' => $cart->total_price,
                    'discount_amount' => $cart->discount_amount,
                    'final_price' => $cart->final_price,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در به‌روزرسانی سبد خرید: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف آیتم از سبد خرید
     *
     * @param Request $request
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem(Request $request, $itemId)
    {
        $user = $request->user();
        
        // دریافت سبد خرید کاربر
        $cart = $this->getOrCreateCart($user->id);
        
        // بررسی وجود آیتم در سبد خرید
        $cartItem = $cart->items()->where('id', $itemId)->first();
        
        if (!$cartItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'آیتم مورد نظر در سبد خرید شما وجود ندارد'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // حذف آیتم از سبد خرید
            $cartItem->delete();
            
            // محاسبه مجدد جمع سبد خرید
            $cart->calculateTotals();
            
            DB::commit();
            
            // بارگذاری مجدد اطلاعات سبد خرید
            $cart->load(['items.product']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'محصول با موفقیت از سبد خرید حذف شد',
                'data' => [
                    'cart' => $cart,
                    'items' => $cart->items,
                    'total_items' => $cart->total_items,
                    'total_price' => $cart->total_price,
                    'discount_amount' => $cart->discount_amount,
                    'final_price' => $cart->final_price,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در حذف محصول از سبد خرید: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * خالی کردن سبد خرید
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        
        // دریافت سبد خرید کاربر
        $cart = $this->getOrCreateCart($user->id);
        
        DB::beginTransaction();
        
        try {
            // حذف تمام آیتم‌های سبد خرید
            $cart->items()->delete();
            
            // بروزرسانی اطلاعات سبد خرید
            $cart->total_items = 0;
            $cart->total_price = 0;
            $cart->discount_amount = 0;
            $cart->final_price = 0;
            $cart->discount_code = null;
            $cart->save();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'سبد خرید با موفقیت خالی شد',
                'data' => [
                    'cart' => $cart,
                    'items' => [],
                    'total_items' => 0,
                    'total_price' => 0,
                    'discount_amount' => 0,
                    'final_price' => 0,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خالی کردن سبد خرید: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * اعمال کد تخفیف به سبد خرید
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyDiscount(Request $request)
    {
        $request->validate([
            'discount_code' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $discountCode = $request->input('discount_code');
        
        // دریافت سبد خرید کاربر
        $cart = $this->getOrCreateCart($user->id);
        
        // بررسی اعتبار کد تخفیف (این بخش باید با سیستم کدهای تخفیف شما هماهنگ شود)
        try {
            $response = app()->make('App\Http\Controllers\Api\DiscountCodeController')->validateCode($request);
            $discountData = json_decode($response->getContent(), true);
            
            if ($discountData['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد تخفیف نامعتبر است'
                ], 400);
            }

            $discountAmount = ($discountData['data']['discount'] / 100) * $cart->total_price;
            
            // اعمال کد تخفیف
            $cart->discount_code = $discountCode;
            $cart->discount_amount = $discountAmount;
            $cart->final_price = $cart->total_price - $discountAmount;
            $cart->save();
            
            // بارگذاری مجدد اطلاعات سبد خرید
            $cart->load(['items.product']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'کد تخفیف با موفقیت اعمال شد',
                'data' => [
                    'cart' => $cart,
                    'items' => $cart->items,
                    'total_items' => $cart->total_items,
                    'total_price' => $cart->total_price,
                    'discount_amount' => $cart->discount_amount,
                    'final_price' => $cart->final_price,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در اعمال کد تخفیف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف کد تخفیف از سبد خرید
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeDiscount(Request $request)
    {
        $user = $request->user();
        
        // دریافت سبد خرید کاربر
        $cart = $this->getOrCreateCart($user->id);
        
        // حذف کد تخفیف
        $cart->discount_code = null;
        $cart->discount_amount = 0;
        $cart->final_price = $cart->total_price;
        $cart->save();
        
        // بارگذاری مجدد اطلاعات سبد خرید
        $cart->load(['items.product']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'کد تخفیف با موفقیت حذف شد',
            'data' => [
                'cart' => $cart,
                'items' => $cart->items,
                'total_items' => $cart->total_items,
                'total_price' => $cart->total_price,
                'discount_amount' => 0,
                'final_price' => $cart->total_price,
            ]
        ]);
    }

    /**
     * دریافت سبد خرید کاربر یا ایجاد سبد جدید
     *
     * @param int $userId
     * @return Cart
     */
    protected function getOrCreateCart($userId)
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'total_price' => 0,
                'total_items' => 0,
                'discount_amount' => 0,
                'final_price' => 0,
            ]);
        }
        
        return $cart;
    }
} 