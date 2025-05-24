<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TicketReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'content' => 'required|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // حداکثر 5MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'content.required' => 'متن پیام الزامی است',
            'content.max' => 'متن پیام نمی‌تواند بیشتر از :max کاراکتر باشد',
            'file.file' => 'فایل آپلود شده معتبر نیست',
            'file.mimes' => 'فقط فایل‌های PDF, JPG, JPEG و PNG قابل قبول هستند',
            'file.max' => 'حجم فایل آپلود شده نمی‌تواند بیشتر از 5 مگابایت باشد',
        ];
    }
} 