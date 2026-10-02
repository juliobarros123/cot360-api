<?php

namespace App\Http\Requests\ENT;

use Illuminate\Foundation\Http\FormRequest;

class StoreENTEntitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        $rawType = $this->input('entity_type', $this->input('type'));

        if (!is_string($rawType)) {
            return;
        }

        $normalized = strtolower(trim($rawType));

        $map = [
            'personal' => 'Personal',
            'person' => 'Personal',
            'individual' => 'Personal',
            'corporate' => 'Corporate',
            'company' => 'Corporate',
            '1' => 'Personal',
            '2' => 'Corporate',
            'business' => 'Corporate',
        ];

        if (isset($map[$normalized])) {
            $this->merge(['entity_type' => $map[$normalized]]);
        }
    }

    public function rules(): array
    {
        return [
            'entity_type'          => 'required|in:Personal,Corporate',
            'name'                 => 'required|string|max:255',
            'nif'                  => 'nullable|string|max:50|unique:ent_entities,nif',
            'id_card'              => 'nullable|string|max:50|unique:ent_entities,id_card',
            'passport'             => 'nullable|string|max:50|unique:ent_entities,passport',
            'identifier_status'    => 'nullable|in:Temporary,Partial,Verified',
            'kyc_status'           => 'nullable|in:Not_Started,Pending,Approved,Rejected,Expired',
            'pep_status'           => 'nullable|in:Not_Checked,Not_PEP,PEP,Related_PEP',
            'us_person'            => 'boolean',
            'status'               => 'nullable|in:Active,Inactive,Blocked,Pending_Compliance',
            // Personal sub-perfil
            'personal'                      => 'nullable|array',
            'personal.date_of_birth'        => 'nullable|date',
            'personal.gender'               => 'nullable|in:Male,Female',
            'personal.id_type'              => 'nullable|in:BI,Passport,Residence_Card,Other',
            'personal.id_expiry'            => 'nullable|date',
            'personal.nationality'          => 'nullable|string|max:50',
            'personal.marital_status'       => 'nullable|string|max:50',
            'personal.profession'           => 'nullable|string|max:100',
            'personal.driver_license_date'  => 'nullable|date',
            // Corporate sub-perfil
            'corporate'                        => 'nullable|array',
            'corporate.legal_name'             => 'nullable|string|max:255',
            'corporate.trade_name'             => 'nullable|string|max:255',
            'corporate.registration_number'    => 'nullable|string|max:50',
            'corporate.registration_date'      => 'nullable|date',
            'corporate.cae_code'               => 'nullable|string|max:20',
            'corporate.industry_type'          => 'nullable|string|max:100',
            'corporate.tax_classification'     => 'nullable|string|max:50',
            // Endereços
            'addresses'                        => 'nullable|array',
            'addresses.*.address_type'         => 'required_with:addresses|string',
            'addresses.*.address'              => 'nullable|string',
            'addresses.*.city'                 => 'nullable|string|max:100',
            'addresses.*.province'             => 'nullable|string|max:100',
            'addresses.*.country'              => 'nullable|string|max:50',
            'addresses.*.is_default'           => 'boolean',
            // Contactos
            'contacts'                         => 'nullable|array',
            'contacts.*.contact_type'          => 'nullable|in:Primary,Billing,Technical,Emergency,Other',
            'contacts.*.name'                  => 'nullable|string|max:255',
            'contacts.*.email'                 => 'nullable|email|max:255',
            'contacts.*.phone'                 => 'nullable|string|max:50',
            'contacts.*.mobile'                => 'nullable|string|max:50',
            'contacts.*.is_default'            => 'boolean',
        ];
    }
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $data = $this->all();
            $hasId = !empty($data['nif']) || !empty($data['id_card']) || !empty($data['passport']);
            if (!$hasId) {
                // Sem identificação — será gerado temp_identifier automaticamente
            }
            if (($data['entity_type'] ?? '') === 'Personal' && empty($data['personal'])) {
                // Personal sem sub-perfil — aceitável (pode ser preenchido depois)
            }
        });
    }
}
