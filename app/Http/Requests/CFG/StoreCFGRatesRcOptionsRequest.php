<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGRatesRcOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rc_base'   => 'required|integer',
            'rc_fac'    => 'required|integer',
            'perc_rate' => 'required|numeric',
        ];
    }
}
