<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceProviderUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $serviceProviderId = $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('service_providers')->ignore($serviceProviderId),
            ],
            'phone' => 'nullable|string|max:20',
            'type' => ['sometimes', 'required', Rule::in(['business_unit', 'connect_partner'])],
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'documents.*' => 'sometimes|required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام خدمات‌دهنده الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلا ثبت شده است.',
            'type.required' => 'نوع خدمات‌دهنده الزامی است.',
            'type.in' => 'نوع خدمات‌دهنده باید یکی از مقادیر مجاز باشد.',
            'website.url' => 'فرمت وب‌سایت صحیح نیست.',
            'documents.*.required' => 'فایل مدرک الزامی است.',
            'documents.*.file' => 'مدرک باید یک فایل معتبر باشد.',
            'documents.*.mimes' => 'فرمت مدرک باید یکی از فرمت‌های pdf، jpg، jpeg یا png باشد.',
            'documents.*.max' => 'حداکثر حجم هر مدرک 10 مگابایت است.',
        ];
    }
}
