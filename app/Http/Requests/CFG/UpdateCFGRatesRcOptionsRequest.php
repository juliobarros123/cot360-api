<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGRatesRcOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rc_base'   => 'sometimes|integer',
            'rc_fac'    => 'sometimes|integer',
            'perc_rate' => 'sometimes|numeric',
        ];
    }
}
