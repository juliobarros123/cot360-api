<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGRatesCaeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cae'                => 'required|string|max:20|unique:cfg_rates_cae,cae',
            'description'        => 'nullable|string',
            'rate_mre_building'  => 'nullable|numeric',
            'rate_mre_content'   => 'nullable|numeric',
            'work_accident'      => 'nullable|numeric',
            'active'             => 'boolean',
        ];
    }
}
