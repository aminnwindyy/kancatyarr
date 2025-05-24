<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TicketStatusUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // فقط ادمین‌ها و پشتیبان‌ها اجازه تغییر وضعیت تیکت را دارند
        return Auth::check() && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('support'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'status' => 'required|in:open,closed,pending',
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
            'status.required' => 'وضعیت تیکت الزامی است',
            'status.in' => 'وضعیت تیکت باید یکی از مقادیر open، closed یا pending باشد',
        ];
    }
} 