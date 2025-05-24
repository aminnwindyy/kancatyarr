<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountingTransactionRequest;
use App\Models\AccountingTransaction;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    /**
     * سرویس حسابداری
     * 
     * @var AccountingService
     */
    protected $accountingService;
    
    /**
     * ایجاد نمونه جدید از کنترلر
     * 
     * @param AccountingService $accountingService
     */
    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        
        // فقط ادمین‌ها یا کاربران با نقش "حسابداری" اجازه دسترسی دارند
        $this->middleware(['auth:sanctum', 'admin'])->except(['summary', 'revenueChart']);
    }
    
    /**
     * دریافت خلاصه وضعیت مالی
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request)
    {
        $period = $request->input('period', 'daily');
        
        // اعتبارسنجی پارامتر دوره
        if (!in_array($period, ['daily', 'monthly', 'yearly'])) {
            return response()->json([
                'status' => false,
                'message' => 'پارامتر دوره باید یکی از مقادیر daily, monthly یا yearly باشد.'
            ], 400);
        }
        
        $summary = $this->accountingService->getBalanceSummary($period);
        
        return response()->json([
            'status' => true,
            'data' => $summary
        ]);
    }
    
    /**
     * دریافت نمودار درآمد
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueChart(Request $request)
    {
        $period = $request->input('period', 'month');
        $limit = (int) $request->input('limit', 12);
        
        // اعتبارسنجی پارامتر دوره
        if (!in_array($period, ['month', 'year'])) {
            return response()->json([
                'status' => false,
                'message' => 'پارامتر دوره باید یکی از مقادیر month یا year باشد.'
            ], 400);
        }
        
        // محدودیت تعداد آیتم‌ها
        if ($limit < 1 || $limit > 24) {
            $limit = 12;
        }
        
        $chart = $this->accountingService->getRevenueChart($period, $limit);
        
        return response()->json([
            'status' => true,
            'data' => $chart
        ]);
    }
    
    /**
     * لیست تراکنش‌ها
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // فیلترها
        $filters = [
            'status' => $request->input('status'),
            'type' => $request->input('type'),
            'user_id' => $request->input('user_id'),
            'provider_id' => $request->input('provider_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];
        
        $perPage = (int) $request->input('per_page', 15);
        
        $transactions = $this->accountingService->listTransactions($filters, $perPage);
        
        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    }
    
    /**
     * نمایش جزئیات یک تراکنش
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $transaction = $this->accountingService->getTransactionDetails($id);
        
        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'تراکنش مورد نظر یافت نشد.'
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'data' => $transaction
        ]);
    }
    
    /**
     * تایید یک تراکنش
     * 
     * @param AccountingTransactionRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(AccountingTransactionRequest $request, $id)
    {
        $trackingCode = $request->input('tracking_code');
        $adminId = Auth::id();
        
        $result = $this->accountingService->approveTransaction($id, $adminId, $trackingCode);
        
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'تراکنش مورد نظر یافت نشد یا قابل تایید نیست.'
            ], 400);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت تایید شد.'
        ]);
    }
    
    /**
     * رد یک تراکنش
     * 
     * @param AccountingTransactionRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(AccountingTransactionRequest $request, $id)
    {
        $reason = $request->input('reason');
        $adminId = Auth::id();
        
        $result = $this->accountingService->rejectTransaction($id, $adminId, $reason);
        
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'تراکنش مورد نظر یافت نشد یا قابل رد نیست.'
            ], 400);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت رد شد.'
        ]);
    }
    
    /**
     * تسویه یک تراکنش
     * 
     * @param AccountingTransactionRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function settle(AccountingTransactionRequest $request, $id)
    {
        $trackingCode = $request->input('tracking_code');
        $adminId = Auth::id();
        
        $result = $this->accountingService->settleTransaction($id, $adminId, $trackingCode);
        
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'تراکنش مورد نظر یافت نشد یا قابل تسویه نیست.'
            ], 400);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت تسویه شد.'
        ]);
    }
    
    /**
     * ایجاد درخواست برداشت کاربر
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUserWithdrawal(Request $request)
    {
        // اعتبارسنجی درخواست
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'amount' => 'required|numeric|min:10000',
            'bank_account' => 'required|string|max:50',
            'metadata' => 'nullable|array'
        ], [
            'user_id.required' => 'شناسه کاربر الزامی است.',
            'user_id.exists' => 'کاربر انتخاب شده معتبر نیست.',
            'amount.required' => 'مبلغ تراکنش الزامی است.',
            'amount.numeric' => 'مبلغ تراکنش باید عدد باشد.',
            'amount.min' => 'مبلغ تراکنش باید حداقل :min ریال باشد.',
            'bank_account.required' => 'شماره حساب بانکی الزامی است.',
            'bank_account.max' => 'شماره حساب بانکی نمی‌تواند بیش از :max کاراکتر باشد.',
        ]);
        
        $userId = $request->input('user_id');
        $amount = $request->input('amount');
        $bankAccount = $request->input('bank_account');
        $metadata = $request->input('metadata', []);
        
        $transaction = $this->accountingService->createUserWithdrawalRequest($userId, $amount, $bankAccount, $metadata);
        
        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ایجاد درخواست برداشت.'
            ], 500);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'درخواست برداشت با موفقیت ایجاد شد.',
            'data' => $transaction
        ], 201);
    }
    
    /**
     * ایجاد درخواست برداشت خدمات‌دهنده
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createProviderWithdrawal(Request $request)
    {
        // اعتبارسنجی درخواست
        $request->validate([
            'provider_id' => 'required|exists:service_providers,id',
            'amount' => 'required|numeric|min:10000',
            'bank_account' => 'required|string|max:50',
            'metadata' => 'nullable|array'
        ], [
            'provider_id.required' => 'شناسه خدمات‌دهنده الزامی است.',
            'provider_id.exists' => 'خدمات‌دهنده انتخاب شده معتبر نیست.',
            'amount.required' => 'مبلغ تراکنش الزامی است.',
            'amount.numeric' => 'مبلغ تراکنش باید عدد باشد.',
            'amount.min' => 'مبلغ تراکنش باید حداقل :min ریال باشد.',
            'bank_account.required' => 'شماره حساب بانکی الزامی است.',
            'bank_account.max' => 'شماره حساب بانکی نمی‌تواند بیش از :max کاراکتر باشد.',
        ]);
        
        $providerId = $request->input('provider_id');
        $amount = $request->input('amount');
        $bankAccount = $request->input('bank_account');
        $metadata = $request->input('metadata', []);
        
        $transaction = $this->accountingService->createProviderWithdrawalRequest($providerId, $amount, $bankAccount, $metadata);
        
        if (!$transaction) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ایجاد درخواست برداشت.'
            ], 500);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'درخواست برداشت با موفقیت ایجاد شد.',
            'data' => $transaction
        ], 201);
    }
}
