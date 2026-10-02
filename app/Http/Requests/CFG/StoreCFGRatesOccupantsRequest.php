<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class StoreCFGRatesOccupantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seat_min' => 'required|integer|min:0',
            'seat_max' => 'required|integer|min:0',
            'package'  => 'required|in:Min,Med,Max',
            'mip'      => 'required|numeric',
            'dt'       => 'required|numeric',
            'df'       => 'required|numeric',
            'premium'  => 'required|numeric',
        ];
    }
}
