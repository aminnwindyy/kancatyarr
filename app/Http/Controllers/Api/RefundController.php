<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\GiftCard;
use App\Models\User;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Notifications\RefundStatusChanged;

class RefundController extends Controller
{
    /**
     * Request a refund for an order
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function requestRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'refund_method' => 'required|in:wallet,gift_card,bank',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($request->order_id);
        
        // Check if user owns the order
        if ($order->user_id != auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'شما مجوز انجام این عملیات را ندارید'], 403);
        }
        
        // Check if order status allows refund
        if (!in_array($order->status, ['completed', 'delivered', 'disputed'])) {
            return response()->json(['message' => 'این سفارش در وضعیت مناسب برای درخواست استرداد وجه نیست'], 400);
        }

        // Check if refund amount is valid
        if ($request->amount > $order->total_amount) {
            return response()->json(['message' => 'مبلغ درخواستی برای استرداد بیشتر از مبلغ سفارش است'], 400);
        }

        try {
            DB::beginTransaction();
            
            // Create payment transaction record
            $transaction = PaymentTransaction::create([
                'order_id' => $order->order_id,
                'user_id' => auth()->id(),
                'refund_method' => $request->refund_method,
                'amount' => $request->amount,
                'status' => 'pending',
                'description' => $request->description,
            ]);
            
            // Update order status
            $order->status = 'refund_requested';
            $order->save();
            
            // Record status change in history
            $order->status_history()->create([
                'status' => 'refund_requested',
                'user_id' => auth()->id(),
                'notes' => 'درخواست استرداد وجه به مبلغ ' . number_format($request->amount) . ' ریال',
            ]);
            
            // Broadcast event for real-time updates
            broadcast(new OrderStatusUpdated($order))->toOthers();
            
            DB::commit();
            
            return response()->json([
                'message' => 'درخواست استرداد وجه با موفقیت ثبت شد',
                'transaction' => $transaction
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطا در ثبت درخواست استرداد: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process a refund request (admin only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:payment_transactions,id',
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check admin permission
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'شما مجوز انجام این عملیات را ندارید'], 403);
        }

        $transaction = PaymentTransaction::findOrFail($request->transaction_id);
        $order = $transaction->order;
        
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'این تراکنش قبلاً پردازش شده است'], 400);
        }

        try {
            DB::beginTransaction();
            
            $transaction->status = $request->status;
            $transaction->save();
            
            if ($request->status === 'approved') {
                // Process based on refund method
                switch ($transaction->refund_method) {
                    case 'wallet':
                        $this->processWalletRefund($transaction, $order);
                        break;
                    case 'gift_card':
                        $this->processGiftCardRefund($transaction, $order);
                        break;
                    case 'bank':
                        $this->processBankRefund($transaction, $order);
                        break;
                }
                
                $order->status = 'refunded';
            } else {
                $order->status = 'refund_rejected';
            }
            
            $order->save();
            
            // Record status change in history
            $order->status_history()->create([
                'status' => $order->status,
                'user_id' => auth()->id(),
                'notes' => $request->notes ?? ($request->status === 'approved' ? 
                    'استرداد وجه به مبلغ ' . number_format($transaction->amount) . ' ریال تایید شد' : 
                    'درخواست استرداد وجه رد شد')
            ]);
            
            // ارسال نوتیفیکیشن به کاربر
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new RefundStatusChanged($transaction));
            }
            
            // ثبت لاگ تراکنش
            \Log::channel('transactions')->info('Refund processed', [
                'transaction_id' => $transaction->id,
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'amount' => $transaction->amount,
                'method' => $transaction->refund_method,
                'status' => $request->status,
                'admin_id' => auth()->id(),
                'processed_at' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Broadcast event for real-time updates
            broadcast(new OrderStatusUpdated($order))->toOthers();
            
            DB::commit();
            
            return response()->json([
                'message' => $request->status === 'approved' ? 
                    'استرداد وجه با موفقیت انجام شد' : 
                    'درخواست استرداد وجه رد شد',
                'transaction' => $transaction
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // ثبت لاگ خطا
            \Log::channel('errors')->error('Error processing refund', [
                'transaction_id' => $transaction->id,
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['message' => 'خطا در پردازش استرداد: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get refund history for a user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserRefunds(Request $request)
    {
        $userId = auth()->id();
        
        $refunds = PaymentTransaction::where('user_id', $userId)
            ->with(['order:order_id,total_amount,status,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return response()->json($refunds);
    }
    
    /**
     * Get all refund requests (admin only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAllRefunds(Request $request)
    {
        // Check admin permission
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'شما مجوز انجام این عملیات را ندارید'], 403);
        }
        
        $query = PaymentTransaction::with(['order:order_id,total_amount,status,created_at', 'user:user_id,name,email']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->ofStatus($request->status);
        }
        
        if ($request->has('refund_method')) {
            $query->ofRefundMethod($request->refund_method);
        }
        
        $refunds = $query->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json($refunds);
    }
    
    /**
     * Process refund to user's wallet
     */
    private function processWalletRefund($transaction, $order)
    {
        $user = User::find($order->user_id);
        $wallet = Wallet::firstOrCreate(['user_id' => $user->user_id]);
        
        // Add amount to wallet
        $wallet->balance += $transaction->amount;
        $wallet->save();
        
        // Create wallet transaction record
        $wallet->transactions()->create([
            'amount' => $transaction->amount,
            'type' => 'credit',
            'description' => 'استرداد وجه سفارش #' . $order->order_id,
            'order_id' => $order->order_id
        ]);
        
        // Update payment transaction
        $transaction->transaction_reference = 'wallet_refund_' . time();
        $transaction->save();
    }
    
    /**
     * Process refund via gift card
     */
    private function processGiftCardRefund($transaction, $order)
    {
        $user = User::find($order->user_id);
        
        // Generate a new gift card
        $giftCard = GiftCard::create([
            'code' => 'GC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'amount' => $transaction->amount,
            'user_id' => $user->user_id,
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
            'description' => 'استرداد وجه سفارش #' . $order->order_id
        ]);
        
        // Update payment transaction
        $transaction->transaction_reference = 'gift_card_' . $giftCard->code;
        $transaction->save();
    }
    
    /**
     * Process refund via bank transfer
     */
    private function processBankRefund($transaction, $order)
    {
        // In a real system, this would integrate with payment gateway
        // For now, just mark the transaction as processed
        
        $transaction->transaction_reference = 'bank_refund_' . time();
        $transaction->save();
    }
}
