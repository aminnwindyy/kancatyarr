<?php

namespace App\Http\Requests;

use App\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class NoticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // فقط ادمین‌ها یا کاربران با نقش "مدیر محتوا" اجازه دسترسی دارند
        return Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasLocalRole('content_manager'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => 'required|string|in:announcement,policy',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target' => 'required|array',
            'target.*' => 'string',
            'status' => 'required|string|in:draft,published',
        ];
        
        // قوانین اضافی برای زمان انتشار
        if ($this->input('status') === Notice::STATUS_PUBLISHED) {
            $rules['publish_at'] = 'nullable|date|after_or_equal:now';
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
            'type' => 'نوع اطلاعیه',
            'title' => 'عنوان',
            'body' => 'متن',
            'target' => 'هدف اطلاعیه',
            'target.*' => 'هدف اطلاعیه',
            'status' => 'وضعیت',
            'publish_at' => 'زمان انتشار',
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
            'type.required' => 'لطفاً نوع اطلاعیه را مشخص کنید.',
            'type.in' => 'نوع اطلاعیه باید یکی از مقادیر اطلاعیه یا قانون باشد.',
            'title.required' => 'لطفاً عنوان اطلاعیه را وارد کنید.',
            'title.max' => 'عنوان اطلاعیه نمی‌تواند بیش از :max کاراکتر باشد.',
            'body.required' => 'لطفاً متن اطلاعیه را وارد کنید.',
            'target.required' => 'لطفاً هدف اطلاعیه را مشخص کنید.',
            'target.array' => 'هدف اطلاعیه باید به صورت آرایه باشد.',
            'status.required' => 'لطفاً وضعیت اطلاعیه را مشخص کنید.',
            'status.in' => 'وضعیت اطلاعیه باید یکی از مقادیر پیش‌نویس یا منتشرشده باشد.',
            'publish_at.date' => 'فرمت زمان انتشار نامعتبر است.',
            'publish_at.after_or_equal' => 'زمان انتشار نمی‌تواند قبل از زمان فعلی باشد.',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // اگر target فقط شامل all است، آن را به آرایه تبدیل کنیم
        if ($this->has('target') && $this->input('target') === Notice::TARGET_ALL) {
            $this->merge([
                'target' => [Notice::TARGET_ALL],
            ]);
        }
    }
}
