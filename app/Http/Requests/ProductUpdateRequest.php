<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductUpdateRequest extends FormRequest
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
            'category_id' => 'sometimes|exists:categories,category_id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'nullable|image|max:2048', // حداکثر 2MB
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
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست',
            'name.max' => 'نام محصول نمی‌تواند بیشتر از :max کاراکتر باشد',
            'price.numeric' => 'قیمت باید یک عدد باشد',
            'price.min' => 'قیمت نمی‌تواند کمتر از صفر باشد',
            'stock.integer' => 'موجودی باید یک عدد صحیح باشد',
            'stock.min' => 'موجودی نمی‌تواند کمتر از صفر باشد',
            'image.image' => 'فایل آپلود شده باید یک تصویر باشد',
            'image.max' => 'حجم تصویر نمی‌تواند بیشتر از 2 مگابایت باشد',
        ];
    }
}
