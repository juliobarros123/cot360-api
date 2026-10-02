<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGInsuranceBranchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            // 'code'                  => 'sometimes|string|max:20|unique:cfg_insurance_branches,code,' . $this->route('id'),
            'name'                  => 'sometimes|string|max:100',
            'description'           => 'nullable|string',
            'requires_coordinates'  => 'boolean',
            'default_validity_days' => 'integer|min:1|max:365',
            'policy_number_prefix'  => 'nullable|string|max:10',
            'active'                => 'boolean',
        ];
    }
}
