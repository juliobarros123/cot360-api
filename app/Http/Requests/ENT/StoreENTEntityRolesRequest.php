<?php

namespace App\Http\Requests\ENT;

use Illuminate\Foundation\Http\FormRequest;

class StoreENTEntityRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'entity_id'           => 'required|exists:ent_entities,id',
            'role_type_id'        => 'required|exists:ent_role_types,id',
            'role_subtype_id'     => 'nullable|exists:ent_role_subtypes,id',
            'license_number'      => 'nullable|string|max:50',
            'license_expiry'      => 'nullable|date',
            'commission_default'  => 'nullable|numeric|min:0|max:100',
            'sales_channel_id'    => 'nullable|exists:ent_sales_channels,id',
            'active'              => 'boolean',
            'valid_from'          => 'nullable|date',
            'valid_until'         => 'nullable|date|after_or_equal:valid_from',
        ];
    }
}
