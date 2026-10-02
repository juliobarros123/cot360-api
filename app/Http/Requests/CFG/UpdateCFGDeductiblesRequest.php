<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGDeductiblesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coverage_id'    => 'sometimes|nullable|exists:cfg_coverages,id',
            'deduct_type'    => 'sometimes|string|max:50',
            'deduct_object'  => 'sometimes|in:CLAIM,CAP_BUILDING,CAP_INSURANCE,INDEMNITY,NA',
            'deduct_percent' => 'sometimes|nullable|numeric|min:0',
            'deduct_value'   => 'sometimes|nullable|numeric|min:0',
            'deduct_min'     => 'sometimes|nullable|numeric|min:0',
            'deduct_max'     => 'sometimes|nullable|numeric|min:0',
            'discount'       => 'sometimes|nullable|numeric|min:0',
            'details'        => 'sometimes|nullable|string',
        ];
    }
}
