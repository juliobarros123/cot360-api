<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGTemplateCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id'   => 'sometimes|exists:cfg_templates,id',
            'coverage_id'   => 'sometimes|exists:cfg_coverages,id',
            'is_mandatory'  => 'sometimes|boolean',
            'display_order' => 'sometimes|nullable|integer|min:1',
        ];
    }
}
