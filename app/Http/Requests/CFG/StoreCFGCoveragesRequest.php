<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGCoveragesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'required|exists:cfg_insurance_branches,id',
            'group_id'            => 'nullable|exists:cfg_coverages,id',
            'seq'                 => 'required|integer',
            'type_cov'            => 'required|string|max:50',
            'short_name'          => 'nullable|string|max:50',
            'name'                => 'nullable|string|max:255',
            'description'         => 'nullable|string|max:255',
            'obj_type'            => 'nullable|string|in:Vehicle,Property,Person,Salary,All',
            'capital_value'       => 'nullable|numeric',
            'rate_type'           => 'nullable|string|in:Percentage,Fixed,Zero,PERCENTAGE,FIXED,ZERO',
            'risk_rate'           => 'nullable|numeric',
            'cov_perc'            => 'nullable|numeric|min:0|max:100',
            'active'              => 'boolean',
        ];
    }
}
