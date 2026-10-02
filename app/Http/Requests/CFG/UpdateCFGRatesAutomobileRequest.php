<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGRatesAutomobileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurance_branch_id' => 'sometimes|nullable|exists:cfg_insurance_branches,id',
            'code'                => 'sometimes|nullable|string|max:50',
            'description'         => 'sometimes|nullable|string',
            'vehicle_type'        => 'sometimes|nullable|string|max:50',
            'category'            => 'sometimes|nullable|string|max:50',
            'engine_min'          => 'sometimes|nullable|integer|min:0',
            'engine_max'          => 'sometimes|nullable|integer|min:0',
            'seat_min'            => 'sometimes|nullable|integer|min:0',
            'seat_max'            => 'sometimes|nullable|integer|min:0',
            'weight_min'          => 'sometimes|nullable|numeric|min:0',
            'weight_max'          => 'sometimes|nullable|numeric|min:0',
            'min_rc_legal_ucf'     => 'sometimes|nullable|numeric|min:0',
            'min_rc_limit_kz'      => 'sometimes|nullable|numeric|min:0',
            'rc_premium_kz'        => 'sometimes|nullable|numeric|min:0',
            'ccc_percent'          => 'sometimes|nullable|numeric|min:0',
            'ire_percent'          => 'sometimes|nullable|numeric|min:0',
            'fr_percent'           => 'sometimes|nullable|numeric|min:0',
            'av_percent'           => 'sometimes|nullable|numeric|min:0',
            'fn_percent'           => 'sometimes|nullable|numeric|min:0',
            'gtaop_percent'        => 'sometimes|nullable|numeric|min:0',
            'active'              => 'sometimes|boolean',
        ];
    }
}
