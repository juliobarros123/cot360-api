<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGRatesHealthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'nullable|exists:cfg_insurance_branches,id',
            'coverage_id'         => 'nullable|exists:cfg_coverages,id',
            'age_min'             => 'nullable|integer|min:0',
            'age_max'             => 'nullable|integer|min:0',
            'gender'              => 'nullable|string|in:M,F,Any',
            'type'                => 'nullable|string|max:50',
            'package'             => 'nullable|string|in:Basic,Standard,Premium',
            'premium_value'       => 'nullable|numeric',
            'active'              => 'boolean',
        ];
    }
}
