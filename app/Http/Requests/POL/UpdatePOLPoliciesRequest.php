<?php

namespace App\Http\Requests\POL;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePOLPoliciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'effective_date'       => 'required|date',
            'subtype'              => 'nullable|string|max:50',
            'net_premium'          => 'nullable|numeric|min:0',
            'total_taxes'          => 'nullable|numeric|min:0',
            'gross_premium'        => 'nullable|numeric|min:0',
            'premium_difference'   => 'nullable|numeric',
            'reason'               => 'nullable|string|max:1000',
        ];
    }
}
