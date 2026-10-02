<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'required|exists:cfg_insurance_branches,id',
            'code'                => 'required|integer',
            'sequence'            => 'nullable|integer',
            'name'                => 'required|string|max:100',
            'description'         => 'nullable|string',
            'package'             => 'nullable|string|',
            'object_type'         => 'nullable|string|in:Vehicle,Property,Person,Salary',
            'qty_objects'         => 'nullable|integer|min:1',
            'active'              => 'boolean',
        ];
    }
}
