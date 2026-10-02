<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGRatesSchoolAccidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'nullable|exists:cfg_insurance_branches,id',
            'coverage_id' => 'nullable|exists:cfg_coverages,id',
            'type' => 'nullable|string|max:50',
            'package' => 'nullable|string|max:50',
            'premium_value' => 'nullable|numeric|min:0',
            'active' => 'nullable|boolean',
            'slug' => 'sometimes|required|string|max:100|unique:cfg_rates_school_accident,slug,' . $this->route('id'),
        ];
    }
}