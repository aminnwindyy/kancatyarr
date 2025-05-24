<?php

namespace App\Http\Requests;

use App\Models\AccountingTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AccountingTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // فقط ادمین‌ها یا کاربران با نقش "حسابداری" اجازه دسترسی دارند
        return Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasLocalRole('accountant'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // بر اساس اکشن، قوانین اعتبارسنجی را مشخص می‌کنیم
        $action = $this->route()->getActionMethod();
        
        // قوانین مشترک
        $rules = [
            'transaction_id' => 'required|exists:accounting_transactions,id',
        ];
        
        if ($action === 'approve') {
            $rules['tracking_code'] = 'nullable|string|max:50';
        } elseif ($action === 'reject') {
            $rules['reason'] = 'required|string|max:500';
        } elseif ($action === 'store') {
            $rules = [
                'user_id' => 'nullable|exists:users,user_id',
                'provider_id' => 'nullable|exists:service_providers,id',
                'type' => 'required|in:' . implode(',', [
                    AccountingTransaction::TYPE_WITHDRAW_USER,
                    AccountingTransaction::TYPE_WITHDRAW_PROVIDER,
                    AccountingTransaction::TYPE_DEPOSIT,
                    AccountingTransaction::TYPE_FEE,
                    AccountingTransaction::TYPE_REFUND,
                    AccountingTransaction::TYPE_SETTLEMENT
                ]),
                'amount' => 'required|numeric|min:1000',
                'bank_account' => 'nullable|string|max:50',
                'reference_id' => 'nullable|string|max:50',
                'metadata' => 'nullable|array'
            ];
            
            // یا کاربر باید مشخص شود یا خدمات‌دهنده
            if ($this->input('type') === AccountingTransaction::TYPE_WITHDRAW_USER) {
                $rules['user_id'] = 'required|exists:users,user_id';
                $rules['bank_account'] = 'required|string|max:50';
            } elseif ($this->input('type') === AccountingTransaction::TYPE_WITHDRAW_PROVIDER) {
                $rules['provider_id'] = 'required|exists:service_providers,id';
                $rules['bank_account'] = 'required|string|max:50';
            }
        }
        
        return $rules;
    }
    
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transaction_id' => 'شناسه تراکنش',
            'tracking_code' => 'کد پیگیری',
            'reason' => 'دلیل رد',
            'user_id' => 'کاربر',
            'provider_id' => 'خدمات‌دهنده',
            'type' => 'نوع تراکنش',
            'amount' => 'مبلغ',
            'bank_account' => 'شماره حساب بانکی',
            'reference_id' => 'شناسه مرجع',
            'metadata' => 'اطلاعات اضافی'
        ];
    }
    
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'شناسه تراکنش الزامی است.',
            'transaction_id.exists' => 'تراکنش موردنظر یافت نشد.',
            'reason.required' => 'دلیل رد تراکنش الزامی است.',
            'reason.max' => 'دلیل رد تراکنش نمی‌تواند بیش از :max کاراکتر باشد.',
            'user_id.required' => 'انتخاب کاربر الزامی است.',
            'user_id.exists' => 'کاربر انتخاب شده معتبر نیست.',
            'provider_id.required' => 'انتخاب خدمات‌دهنده الزامی است.',
            'provider_id.exists' => 'خدمات‌دهنده انتخاب شده معتبر نیست.',
            'type.required' => 'نوع تراکنش الزامی است.',
            'type.in' => 'نوع تراکنش معتبر نیست.',
            'amount.required' => 'مبلغ تراکنش الزامی است.',
            'amount.numeric' => 'مبلغ تراکنش باید عدد باشد.',
            'amount.min' => 'مبلغ تراکنش باید حداقل :min ریال باشد.',
            'bank_account.required' => 'شماره حساب بانکی الزامی است.',
            'bank_account.max' => 'شماره حساب بانکی نمی‌تواند بیش از :max کاراکتر باشد.',
        ];
    }
}
