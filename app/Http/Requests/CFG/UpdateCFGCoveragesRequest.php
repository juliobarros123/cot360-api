<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGCoveragesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'sometimes|exists:cfg_insurance_branches,id',
            'group_id'            => 'sometimes|nullable|exists:cfg_coverages,id',
            'seq'                 => 'sometimes|integer',
            'type_cov'            => 'sometimes|string|max:50',
            'short_name'          => 'sometimes|nullable|string|max:50',
            'name'                => 'sometimes|nullable|string|max:255',
            'description'         => 'sometimes|nullable|string|max:255',
            'obj_type'            => 'sometimes|nullable|string|in:Vehicle,Property,Person,Salary,All',
            'capital_value'       => 'sometimes|nullable|numeric',
            'rate_type'           => 'sometimes|nullable|string|in:Percentage,Fixed,Zero,PERCENTAGE,FIXED,ZERO',
            'risk_rate'           => 'sometimes|nullable|numeric',
            'cov_perc'            => 'sometimes|nullable|numeric|min:0|max:100',
            'active'              => 'sometimes|boolean',
        ];
    }
}
