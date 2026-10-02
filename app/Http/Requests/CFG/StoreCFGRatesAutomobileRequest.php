<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGRatesAutomobileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'nullable|exists:cfg_insurance_branches,id',
            'code'                => 'nullable|string|max:50',
            'description'         => 'nullable|string',
            'vehicle_type'        => 'nullable|string|max:50',
            'category'            => 'nullable|string|max:50',
            'engine_min'          => 'nullable|integer|min:0',
            'engine_max'          => 'nullable|integer|min:0',
            'seat_min'            => 'nullable|integer|min:0',
            'seat_max'            => 'nullable|integer|min:0',
            'weight_min'          => 'nullable|numeric|min:0',
            'weight_max'          => 'nullable|numeric|min:0',
            'min_rc_legal_ucf'     => 'nullable|numeric|min:0',
            'min_rc_limit_kz'      => 'nullable|numeric|min:0',
            'rc_premium_kz'        => 'nullable|numeric|min:0',
            'ccc_percent'          => 'nullable|numeric|min:0',
            'ire_percent'          => 'nullable|numeric|min:0',
            'fr_percent'           => 'nullable|numeric|min:0',
            'av_percent'           => 'nullable|numeric|min:0',
            'fn_percent'           => 'nullable|numeric|min:0',
            'gtaop_percent'        => 'nullable|numeric|min:0',
            'active'              => 'boolean',
        ];
    }
}
