<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGSpecialConditionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'required|exists:cfg_insurance_branches,id',
            'code'                => 'nullable|string|max:50',
            'condition_type'      => 'required|in:Inclusion,Exclusion,Clause,Warranty',
            'short_text'          => 'required|string|max:500',
            'long_text'           => 'nullable|string',
            'active'              => 'boolean',
        ];
    }
}
