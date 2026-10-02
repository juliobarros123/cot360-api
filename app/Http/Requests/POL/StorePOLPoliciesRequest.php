<?php

namespace App\Http\Requests\POL;

use Illuminate\Foundation\Http\FormRequest;

class StorePOLPoliciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'quotation_id'            => 'required|exists:quo_quotations,id',
            'quotation_version_id'    => 'required|exists:quo_quotation_versions,id',
            'inception_date'          => 'required|date',
            'expiry_date'             => 'required|date|after:inception_date',
            'net_premium'             => 'required|numeric|min:0',
            'total_taxes'             => 'required|numeric|min:0',
            'gross_premium'           => 'required|numeric|min:0',
            'gross_premium_usd'       => 'nullable|numeric|min:0',
            'commission_rate'         => 'nullable|numeric|min:0|max:100',
            'commission_value'        => 'nullable|numeric|min:0',
            'previous_policy_id'      => 'nullable|exists:pol_policies,id',
            'renewal_count'           => 'nullable|integer|min:0',
            'original_policy_id'      => 'nullable|exists:pol_policies,id',
        ];
    }
}
