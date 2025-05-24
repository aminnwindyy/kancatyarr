<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // فقط ادمین‌ها اجازه تغییر وضعیت تایید را دارند
        return Auth::check() && Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'approval_status' => 'required|in:approved,rejected,pending',
            'approval_reason' => 'nullable|string|max:500',
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
            'approval_status.required' => 'وضعیت تایید الزامی است',
            'approval_status.in' => 'وضعیت تایید باید یکی از مقادیر مجاز باشد',
            'approval_reason.max' => 'توضیحات نمی‌تواند بیشتر از :max کاراکتر باشد',
        ];
    }
}
