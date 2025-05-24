<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BankCard;
use App\Models\GiftCard;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FinancialController extends Controller
{
    /**
     * دریافت اطلاعات مالی و تراکنش‌های کاربر
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFinancialDashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        // دریافت یا ایجاد کیف پول در صورت عدم وجود
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->user_id],
            ['balance' => 0, 'gift_balance' => 0]
        );

        // دریافت کارت بانکی فعال کاربر
        $activeCard = BankCard::where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        // دریافت کارت هدیه های فعال کاربر
        $giftCards = GiftCard::where('user_id', $user->user_id)
            ->where('status', 'active')
            ->where('is_used', false)
            ->where('expiry_date', '>=', now())
            ->get();

        // دریافت تراکنش‌های اخیر کاربر
        $recentTransactions = Transaction::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => [
                    'balance' => $wallet->balance,
                    'gift_balance' => $wallet->gift_balance,
                ],
                'active_bank_card' => $activeCard,
                'gift_cards' => $giftCards,
                'recent_transactions' => $recentTransactions,
            ],
        ]);
    }

    /**
     * لیست همه تراکنش‌های کاربر
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactions(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $perPage = $request->get('per_page', 10);
        $type = $request->get('type', null);
        $status = $request->get('status', null);

        $transactions = Transaction::where('user_id', $user->user_id)
            ->when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * دریافت جزئیات یک تراکنش
     *
     * @param int $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionDetails($transactionId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $transaction = Transaction::where('transaction_id', $transactionId)
            ->where('user_id', $user->user_id)
            ->with(['giftCard', 'bankCard', 'order'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'تراکنش مورد نظر یافت نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * دریافت لیست کارت‌های بانکی کاربر
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankCards()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $cards = BankCard::where('user_id', $user->user_id)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cards,
        ]);
    }

    /**
     * افزودن کارت بانکی جدید
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addBankCard(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'card_number' => 'required|string|size:16',
            'sheba_number' => 'nullable|string|size:26',
            'bank_name' => 'required|string|max:255',
            'expiry_date' => 'required|date|after:today',
            'cvv' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        // غیرفعال کردن کارت‌های فعلی در صورت نیاز
        if ($request->has('set_as_active') && $request->set_as_active) {
            BankCard::where('user_id', $user->user_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        // ایجاد کارت جدید
        $card = new BankCard([
            'user_id' => $user->user_id,
            'card_number' => $request->card_number,
            'sheba_number' => $request->sheba_number,
            'bank_name' => $request->bank_name,
            'expiry_date' => $request->expiry_date,
            'cvv' => $request->cvv,
            'is_active' => $request->has('set_as_active') ? $request->set_as_active : false,
        ]);

        $card->save();

        return response()->json([
            'success' => true,
            'message' => 'کارت بانکی با موفقیت اضافه شد',
            'data' => $card,
        ]);
    }

    /**
     * ویرایش کارت بانکی
     *
     * @param Request $request
     * @param int $cardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBankCard(Request $request, $cardId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $card = BankCard::where('card_id', $cardId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'کارت مورد نظر یافت نشد',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'card_number' => 'sometimes|string|size:16',
            'sheba_number' => 'nullable|string|size:26',
            'bank_name' => 'sometimes|string|max:255',
            'expiry_date' => 'sometimes|date|after:today',
            'cvv' => 'sometimes|string|size:3',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        // غیرفعال کردن کارت‌های فعلی در صورت نیاز
        if ($request->has('is_active') && $request->is_active) {
            BankCard::where('user_id', $user->user_id)
                ->where('is_active', true)
                ->where('card_id', '!=', $cardId)
                ->update(['is_active' => false]);
        }

        // بروزرسانی اطلاعات کارت
        $card->fill($request->only([
            'card_number',
            'sheba_number',
            'bank_name',
            'expiry_date',
            'cvv',
            'is_active',
        ]));

        $card->save();

        return response()->json([
            'success' => true,
            'message' => 'کارت بانکی با موفقیت بروزرسانی شد',
            'data' => $card,
        ]);
    }

    /**
     * حذف کارت بانکی
     *
     * @param int $cardId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBankCard($cardId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $card = BankCard::where('card_id', $cardId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'کارت مورد نظر یافت نشد',
            ], 404);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'کارت بانکی با موفقیت حذف شد',
        ]);
    }

    /**
     * دریافت کارت‌های هدیه کاربر
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGiftCards()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $giftCards = GiftCard::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $giftCards,
        ]);
    }

    /**
     * ایجاد کارت هدیه (فقط برای ادمین)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createGiftCard(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'amount' => 'required|numeric|min:1',
            'expiry_date' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetUser = User::find($request->user_id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد',
            ], 404);
        }

        // ایجاد یک شماره کارت تصادفی
        $cardNumber = strtoupper(Str::random(24));

        // ایجاد کارت هدیه
        $giftCard = new GiftCard([
            'user_id' => $targetUser->user_id,
            'created_by' => $user->user_id,
            'card_number' => $cardNumber,
            'amount' => $request->amount,
            'expiry_date' => $request->expiry_date,
            'status' => 'active',
        ]);

        $giftCard->save();

        return response()->json([
            'success' => true,
            'message' => 'کارت هدیه با موفقیت ایجاد شد',
            'data' => $giftCard,
        ]);
    }

    /**
     * استفاده از کارت هدیه
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function useGiftCard(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'card_number' => 'required|string|exists:gift_cards,card_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        $giftCard = GiftCard::where('card_number', $request->card_number)
            ->where('status', 'active')
            ->where('is_used', false)
            ->where('expiry_date', '>=', now())
            ->first();

        if (!$giftCard) {
            return response()->json([
                'success' => false,
                'message' => 'کارت هدیه معتبر نیست یا قبلاً استفاده شده است',
            ], 400);
        }

        // بررسی حق مالکیت کارت هدیه
        if ($giftCard->user_id && $giftCard->user_id != $user->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'این کارت هدیه متعلق به شما نیست',
            ], 403);
        }

        // انجام تراکنش در یک تراکنش دیتابیسی
        DB::beginTransaction();

        try {
            // دریافت یا ایجاد کیف پول کاربر
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->user_id],
                ['balance' => 0, 'gift_balance' => 0]
            );

            // بروزرسانی موجودی هدیه کاربر
            $wallet->gift_balance += $giftCard->amount;
            $wallet->save();

            // بروزرسانی وضعیت کارت هدیه
            $giftCard->is_used = true;
            $giftCard->used_at = now();
            $giftCard->status = 'used';

            // تخصیص به کاربر در صورتی که قبلا تخصیص داده نشده باشد
            if (!$giftCard->user_id) {
                $giftCard->user_id = $user->user_id;
            }

            $giftCard->save();

            // ثبت تراکنش
            $transaction = new Transaction([
                'user_id' => $user->user_id,
                'wallet_id' => $wallet->wallet_id,
                'gift_card_id' => $giftCard->gift_card_id,
                'type' => 'gift',
                'amount' => $giftCard->amount,
                'description' => 'شارژ کیف پول با کارت هدیه',
                'status' => 'completed',
                'payment_method' => 'gift_card',
                'reference_code' => Str::random(10),
            ]);

            $transaction->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'کارت هدیه با موفقیت استفاده شد',
                'data' => [
                    'transaction' => $transaction,
                    'wallet' => $wallet,
                    'gift_card' => $giftCard,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در استفاده از کارت هدیه',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
