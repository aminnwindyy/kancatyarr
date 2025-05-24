<?php

namespace App\Http\Requests;

use App\Models\MediaItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MediaItemRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'link' => 'nullable|string|max:255',
            'order' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'position' => 'required|string|in:top,bottom,sidebar,main_slider,popup',
            'provider' => 'required|string|in:custom,yektanet,tapsell,other',
            'script_code' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];

        // قوانین اعتبارسنجی برای ایجاد آیتم جدید
        if ($this->isMethod('post')) {
            $rules['type'] = 'required|string|in:banner,slider';
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
        }
        
        // قوانین اعتبارسنجی برای به‌روزرسانی آیتم
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
        }

        // اگر نوع تبلیغات yektanet یا tapsell باشد، کد اسکریپت اجباری است
        if (in_array($this->provider, ['yektanet', 'tapsell', 'other']) && $this->provider !== 'custom') {
            $rules['script_code'] = 'required|string';
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
            'type' => 'نوع',
            'title' => 'عنوان',
            'image' => 'تصویر',
            'link' => 'لینک',
            'order' => 'ترتیب نمایش',
            'is_active' => 'وضعیت',
            'position' => 'موقعیت نمایش',
            'provider' => 'سرویس‌دهنده تبلیغات',
            'script_code' => 'کد اسکریپت',
            'start_date' => 'تاریخ شروع',
            'end_date' => 'تاریخ پایان',
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
            'type.required' => 'لطفاً نوع را مشخص کنید.',
            'type.in' => 'نوع باید بنر یا اسلایدر باشد.',
            'title.required' => 'لطفاً عنوان را وارد کنید.',
            'order.required' => 'لطفاً ترتیب نمایش را وارد کنید.',
            'order.integer' => 'ترتیب نمایش باید عدد صحیح باشد.',
            'order.min' => 'ترتیب نمایش نمی‌تواند منفی باشد.',
            'image.required' => 'لطفاً یک تصویر انتخاب کنید.',
            'image.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'image.mimes' => 'فرمت تصویر باید jpeg، png یا jpg باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از 2 مگابایت باشد.',
            'position.required' => 'لطفاً موقعیت نمایش را انتخاب کنید.',
            'position.in' => 'موقعیت نمایش باید یکی از مقادیر معتبر باشد.',
            'provider.required' => 'لطفاً سرویس‌دهنده تبلیغات را انتخاب کنید.',
            'provider.in' => 'سرویس‌دهنده تبلیغات باید یکی از مقادیر معتبر باشد.',
            'script_code.required' => 'وقتی از سرویس‌های تبلیغاتی استفاده می‌کنید، کد اسکریپت الزامی است.',
            'start_date.date' => 'فرمت تاریخ شروع نمایش نامعتبر است.',
            'end_date.date' => 'فرمت تاریخ پایان نمایش نامعتبر است.',
            'end_date.after_or_equal' => 'تاریخ پایان باید بعد از یا برابر با تاریخ شروع باشد.',
        ];
    }
}
