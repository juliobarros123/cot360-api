<?php

namespace App\Http\Requests\QUO;

class UpdateQUOQuotationVersionsRequest extends StoreQUOQuotationsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules['customer_id'] = 'nullable|exists:ent_entities,id';
        $rules['insurance_branch_id'] = 'nullable|exists:cfg_insurance_branches,id';
        $rules['version.valid_until'] = 'nullable|date|after:today';
        $rules['version.template_code'] = 'nullable|numeric|max:50';

        return $rules;
    }
}
