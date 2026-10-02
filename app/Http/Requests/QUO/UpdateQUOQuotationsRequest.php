<?php

namespace App\Http\Requests\QUO;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQUOQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'status' => 'required|in:Draft,Pending,Sent,Accepted,Rejected,Expired,Converted',
        ];
    }
}
