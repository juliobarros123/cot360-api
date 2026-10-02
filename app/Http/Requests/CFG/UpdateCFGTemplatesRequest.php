<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'sometimes|exists:cfg_insurance_branches,id',
            'code'                => 'sometimes|integer',
            'sequence'            => 'sometimes|nullable|integer',
            'name'                => 'sometimes|string|max:100',
            'description'         => 'sometimes|nullable|string',
            'package'             => 'sometimes|nullable|string',
            'object_type'         => 'sometimes|nullable|string|in:Vehicle,Property,Person,Salary',
            'qty_objects'         => 'sometimes|nullable|integer|min:1',
            'active'              => 'sometimes|boolean',
        ];
    }
}
