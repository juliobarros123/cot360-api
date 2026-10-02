<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGRatesCaeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cae'                => 'sometimes|string|max:20|unique:cfg_rates_cae,cae,' . $this->route('id'),
            'description'        => 'sometimes|nullable|string',
            'rate_mre_building'  => 'sometimes|nullable|numeric',
            'rate_mre_content'   => 'sometimes|nullable|numeric',
            'work_accident'      => 'sometimes|nullable|numeric',
            'active'             => 'sometimes|boolean',
        ];
    }
}
