<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * IMPORTANT: Admin can ONLY update order_status (fulfillment status).
     * payment_status is controlled EXCLUSIVELY by Midtrans webhook.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Only order_status values are allowed (fulfillment workflow).
     * payment_status cannot be changed via this request.
     */
    public function rules(): array
    {
        return [
            'order_status' => ['required', 'in:new,processing,shipped,completed,refunded'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_status.required' => 'Order status is required.',
            'order_status.in' => 'Invalid order status. Allowed values: new, processing, shipped, completed, refunded.',
        ];
    }
}
