<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGDeductiblesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'coverage_id'    => 'nullable|exists:cfg_coverages,id',
            'deduct_type'    => 'required|string|max:50',
            'deduct_object'  => 'required|in:CLAIM,CAP_BUILDING,CAP_INSURANCE,INDEMNITY,NA',
            'deduct_percent' => 'nullable|numeric|min:0',
            'deduct_value'   => 'nullable|numeric|min:0',
            'deduct_min'     => 'nullable|numeric|min:0',
            'deduct_max'     => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'details'        => 'nullable|string',
        ];
    }
}
