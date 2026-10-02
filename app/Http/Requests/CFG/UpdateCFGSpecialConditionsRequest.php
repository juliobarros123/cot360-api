<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGSpecialConditionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'sometimes|exists:cfg_insurance_branches,id',
            'code'                => 'sometimes|nullable|string|max:50',
            'condition_type'      => 'sometimes|in:Inclusion,Exclusion,Clause,Warranty',
            'short_text'          => 'sometimes|string|max:500',
            'long_text'           => 'sometimes|nullable|string',
            'active'              => 'sometimes|boolean',
        ];
    }
}
