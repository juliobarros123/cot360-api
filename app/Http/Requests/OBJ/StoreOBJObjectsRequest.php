<?php

namespace App\Http\Requests\OBJ;

use Illuminate\Foundation\Http\FormRequest;

class StoreOBJObjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'entity_id'          => 'nullable|exists:ent_entities,id',
            'type'               => 'required|in:Vehicle,Property,Person,SalaryAggregate,studentCount,Other',
            'description'        => 'nullable|string|max:255',
            // Vehicle
            'detail.plate'              => 'nullable|string|max:20',
            'detail.chassis_number'     => 'nullable|string|max:50',
            'detail.brand'              => 'nullable|string|max:50',
            'detail.model'              => 'nullable|string|max:50',
            'detail.color'              => 'nullable|string|max:30',
            'detail.type'               => 'nullable|string|max:50',
            'detail.cylinder_cc'        => 'nullable|integer|min:0',
            'detail.number_seats'       => 'nullable|integer|min:1',
            'detail.weight'             => 'nullable|integer|min:0',
            'detail.first_plate_date'   => 'nullable|date',
            'detail.dp_capital'         => 'nullable|numeric|min:0',
            'detail.dp_initial_capital' => 'nullable|numeric|min:0',
            'detail.rc_capital'         => 'nullable|numeric|min:0',
            // Property
            'detail.address'            => 'nullable|string',
            'detail.city'               => 'nullable|string|max:100',
            'detail.province'           => 'nullable|string|max:100',
            'detail.property_type'      => 'nullable|string',
            'detail.plan_name'          => 'nullable|string|max:100',
            'detail.risk_class'         => 'nullable|string|max:100',
            'detail.insured_role'       => 'nullable|string|max:100',
            'detail.residence_type'     => 'nullable|string|max:100',
            'detail.building_capital'   => 'nullable|numeric|min:0',
            'detail.content_capital'    => 'nullable|numeric|min:0',
            'detail.exploration_capital' => 'nullable|numeric|min:0',
            'detail.theft_protection'   => 'nullable|boolean',
            'detail.latitude'           => 'nullable|numeric|between:-90,90',
            'detail.longitude'          => 'nullable|numeric|between:-180,180',
            // Person
            'detail.name'               => 'nullable|string|max:255',
            'detail.gender'             => 'nullable|in:Male,Female',
            'detail.birthdate'          => 'nullable|date',
            'detail.age'                => 'nullable|integer|min:0|max:120',
            'detail.dependent'          => 'boolean',
            'detail.relationship'       => 'nullable|string|max:50',
            'detail.job_category'       => 'nullable|in:I,II,III,IV,Not specified',
            'detail.salary'             => 'nullable|numeric|min:0',
            // SalaryAggregate
            'detail.total_base_salary'          => 'nullable|numeric|min:0',
            'detail.total_addons_salary'         => 'nullable|numeric|min:0',
            'detail.total_avg_variable_salary'   => 'nullable|numeric|min:0',
            'detail.number_of_months'            => 'nullable|integer|min:1|max:12',
            'detail.employee_count'              => 'nullable|integer|min:1',
            // studentCount (NOVO)
            'detail.student_count'              => 'nullable|integer|min:0',
        ];
    }
}
