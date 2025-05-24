<?php

namespace App\Http\Requests;

use App\Models\AdSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AdSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // فقط کاربران ادمین اجازه دسترسی دارند
        return Auth::check() && Auth::user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'placement' => 'required|string|max:100',
            'position_id' => 'required|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];

        // قوانین اعتبارسنجی برای ایجاد آیتم جدید
        if ($this->isMethod('post')) {
            $rules['service'] = 'required|string|in:' . implode(',', AdSetting::getAllowedServices());
            $rules['config'] = 'sometimes|json';
        }
        
        // قوانین اعتبارسنجی برای به‌روزرسانی آیتم
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['config'] = 'sometimes|json';
        }

        // اعتبارسنجی اضافی برای تنظیمات سرویس
        $service = $this->input('service');
        if ($service == AdSetting::SERVICE_YEKTANET) {
            $rules['config.api_key'] = 'required|string';
        } elseif ($service == AdSetting::SERVICE_TAPSELL) {
            $rules['config.app_id'] = 'required|string';
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
            'service' => 'سرویس تبلیغات',
            'placement' => 'نقطه نمایش',
            'position_id' => 'شناسه پوزیشن',
            'order' => 'ترتیب نمایش',
            'is_active' => 'وضعیت',
            'config' => 'تنظیمات',
            'config.api_key' => 'کلید API یکتانت',
            'config.app_id' => 'شناسه اپلیکیشن تپسل',
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
            'service.required' => 'لطفاً سرویس تبلیغات را مشخص کنید.',
            'service.in' => 'سرویس تبلیغات باید یکتانت یا تپسل باشد.',
            'placement.required' => 'لطفاً نقطه نمایش را وارد کنید.',
            'position_id.required' => 'لطفاً شناسه پوزیشن را وارد کنید.',
            'order.required' => 'لطفاً ترتیب نمایش را وارد کنید.',
            'order.integer' => 'ترتیب نمایش باید عدد صحیح باشد.',
            'order.min' => 'ترتیب نمایش نمی‌تواند منفی باشد.',
            'config.json' => 'فرمت تنظیمات باید JSON معتبر باشد.',
            'config.api_key.required' => 'برای سرویس یکتانت، کلید API الزامی است.',
            'config.app_id.required' => 'برای سرویس تپسل، شناسه اپلیکیشن الزامی است.',
        ];
    }
} 