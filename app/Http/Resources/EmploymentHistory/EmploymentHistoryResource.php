<?php

namespace App\Http\Resources\EmploymentHistory;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploymentHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'company_location' => $this->company_location,
            'position_name' => $this->position_name,
            'employment_type' => $this->employment_type,
            'employment_type_label' => EmploymentType::labelFromValue($this->employment_type),
            'contract_type' => $this->contract_type,
            'contract_type_label' => ContractType::labelFromValue($this->contract_type),
            'seniority_level' => $this->seniority_level,
            'seniority_level_label' => SeniorityLevelEnum::labelFromValue($this->seniority_level),
            'actuation_details' => $this->actuation_details,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_current' => $this->is_current,
            'dev_profile_id' => $this->dev_profile_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
