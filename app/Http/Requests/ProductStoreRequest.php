<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductStoreRequest extends FormRequest
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
            'service_provider_id' => 'required|exists:service_providers,id',
            'category_id' => 'required|exists:categories,category_id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
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
            'service_provider_id.required' => 'شناسه خدمات‌دهنده الزامی است',
            'service_provider_id.exists' => 'خدمات‌دهنده انتخاب شده معتبر نیست',
            'category_id.required' => 'دسته‌بندی محصول الزامی است',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست',
            'name.required' => 'نام محصول الزامی است',
            'name.max' => 'نام محصول نمی‌تواند بیشتر از :max کاراکتر باشد',
            'description.required' => 'توضیحات محصول الزامی است',
            'price.required' => 'قیمت محصول الزامی است',
            'price.numeric' => 'قیمت باید یک عدد باشد',
            'price.min' => 'قیمت نمی‌تواند کمتر از صفر باشد',
            'stock.required' => 'موجودی محصول الزامی است',
            'stock.integer' => 'موجودی باید یک عدد صحیح باشد',
            'stock.min' => 'موجودی نمی‌تواند کمتر از صفر باشد',
            'image.image' => 'فایل آپلود شده باید یک تصویر باشد',
            'image.max' => 'حجم تصویر نمی‌تواند بیشتر از 2 مگابایت باشد',
        ];
    }
}
