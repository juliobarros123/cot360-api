<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGTemplateCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id'   => 'required|exists:cfg_templates,id',
            'coverage_id'   => 'required|exists:cfg_coverages,id',
            'is_mandatory'  => 'boolean',
            'display_order' => 'nullable|integer|min:1',
        ];
    }
}
