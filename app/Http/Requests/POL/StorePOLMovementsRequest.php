<?php

namespace App\Http\Requests\POL;

use Illuminate\Foundation\Http\FormRequest;

class StorePOLMovementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'policy_id'          => 'required|exists:pol_policies,id',
            'to_version_id'      => 'required|exists:pol_policy_versions,id',
            'movement_type'      => 'required|in:New,Renewal,Amendment,Cancellation,Reinstatement,Correction',
            'movement_subtype'   => 'nullable|string|max:50',
            'movement_date'      => 'required|date',
            'effective_date'     => 'required|date',
            'premium_difference' => 'nullable|numeric',
            'reason'             => 'nullable|string|max:1000',
            'notes'              => 'nullable|string',
        ];
    }
}
