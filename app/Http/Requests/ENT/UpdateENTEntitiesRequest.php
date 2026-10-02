<?php

namespace App\Http\Requests\ENT;

use Illuminate\Foundation\Http\FormRequest;

class UpdateENTEntitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name'              => 'sometimes|string|max:255',
            'nif'               => 'nullable|string|max:50|unique:ent_entities,nif,' . $id,
            'id_card'           => 'nullable|string|max:50|unique:ent_entities,id_card,' . $id,
            'passport'          => 'nullable|string|max:50|unique:ent_entities,passport,' . $id,
            'identifier_status' => 'nullable|in:Temporary,Partial,Verified',
            'kyc_status'        => 'nullable|in:Not_Started,Pending,Approved,Rejected,Expired',
            'pep_status'        => 'nullable|in:Not_Checked,Not_PEP,PEP,Related_PEP',
            'us_person'         => 'boolean',
            'status'            => 'nullable|in:Active,Inactive,Blocked,Pending_Compliance',
        ];
    }
}
