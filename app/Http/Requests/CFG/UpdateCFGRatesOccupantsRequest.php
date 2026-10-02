<?php

namespace App\Http\Requests\CFG;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFGRatesOccupantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seat_min' => 'sometimes|integer|min:0',
            'seat_max' => 'sometimes|integer|min:0',
            'package'  => 'sometimes|in:Min,Med,Max',
            'mip'      => 'sometimes|numeric',
            'dt'       => 'sometimes|numeric',
            'df'       => 'sometimes|numeric',
            'premium'  => 'sometimes|numeric',
        ];
    }
}
