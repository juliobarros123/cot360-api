<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGRatesHealthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'sometimes|nullable|exists:cfg_insurance_branches,id',
            'coverage_id'         => 'sometimes|nullable|exists:cfg_coverages,id',
            'age_min'             => 'sometimes|nullable|integer|min:0',
            'age_max'             => 'sometimes|nullable|integer|min:0',
            'gender'              => 'sometimes|nullable|string|in:M,F,Any',
            'type'                => 'sometimes|nullable|string|max:50',
            'package'             => 'sometimes|nullable|string|in:Basic,Standard,Premium',
            'premium_value'       => 'sometimes|nullable|numeric',
            'active'              => 'sometimes|boolean',
        ];
    }
}
