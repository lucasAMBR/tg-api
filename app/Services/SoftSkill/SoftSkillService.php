<?php

namespace App\Services\SoftSkill;

use App\Enums\SeniorityLevelEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\CompanySoftSkill\CompanySoftSkillResource;
use App\Http\Resources\DevSoftSkill\DevSoftSkillResource;
use App\Http\Resources\SoftSkill\SoftSkillResource;
use App\Models\CompanySoftSkill;
use App\Models\DevProfile;
use App\Models\DevSoftSkill;
use App\Models\SoftSkill;
use App\Models\SoftSkillLevelResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class SoftSkillService
{
    public function index(array $data)
    {
        $softSkills = SoftSkill::with(['responses'])->get();

        return SoftSkillResource::collection($softSkills);
    }

    public function storeDevSoftSkills(array $data)
    {
        $authUser = Auth::user();

        $profile = ProfileHelper::getUserProfileByRole($authUser);

        $softSkillLevelIds = collect($data['soft_skills'])
            ->pluck('soft_skill_level_response_id')
            ->toArray();

        $totalPoints = SoftSkillLevelResponse::whereIn('id', $softSkillLevelIds)
            ->sum('evaluation_weight');

        if($totalPoints >= $this->getPointLimitBasedOnSeniorityLevel($profile)){
            throw new ApiException("You cannot exceed the pontuation limit based on your seniority level!");
        }else if($totalPoints < 10) {
            throw new ApiException("You must have at least one point on every skill!");
        }

        foreach($data['soft_skills'] as $softSkill){
            $skillDefinition = SoftSkill::find($softSkill['soft_skill_id']);

            if(!$skillDefinition || !$skillDefinition->hasLevelResponse($softSkill['soft_skill_level_response_id'])){
                throw new ApiException("Invalid level for skill: {$skillDefinition->name}");
            }

            DevSoftSkill::create([
                'soft_skill_id' => $softSkill['soft_skill_id'],
                'soft_skill_level_response_id' => $softSkill['soft_skill_level_response_id'],
                'dev_profile_id' => $profile->id
            ]);
        }

        $profile->refresh();

        return DevSoftSkillResource::collection($profile->dev_soft_skills);
    }

    public function updateDevSoftSkills(array $data)
    {
        $authUser = Auth::user();

        $profile = ProfileHelper::getUserProfileByRole($authUser);

        $softSkillLevelIds = collect($data['soft_skills'])
            ->pluck('soft_skill_level_response_id')
            ->toArray();

        $totalPoints = SoftSkillLevelResponse::whereIn('id', $softSkillLevelIds)
            ->sum('evaluation_weight');

        if($totalPoints > $this->getPointLimitBasedOnSeniorityLevel($profile)){
            throw new ApiException("You cannot exceed the pontuation limit based on your seniority level!");
        }else if($totalPoints < 10) {
            throw new ApiException("You must have at least one point on every skill!");
        }

        foreach($data['soft_skills'] as $softSkill){
            $skillDefinition = SoftSkill::find($softSkill['soft_skill_id']);

            if(!$skillDefinition || !$skillDefinition->hasLevelResponse($softSkill['soft_skill_level_response_id'])){
                throw new ApiException("Invalid level for skill: {$skillDefinition->name}");
            }

            DevSoftSkill::where('dev_profile_id', $profile->id)
                ->where('soft_skill_id', $softSkill['soft_skill_id'])
                ->update(['soft_skill_level_response_id' => $softSkill['soft_skill_level_response_id']]);
        }

        $profile->refresh();

        return DevSoftSkillResource::collection($profile->dev_soft_skills);
    }

    public function getPointLimitBasedOnSeniorityLevel(DevProfile $profile): int
    {
        $limits = SeniorityLevelEnum::softSkillsPointLimit();

        return $limits[$profile->seniority_level] ?? 25;
    }

    public function storeCompanySoftSkills(array $data) {

        // Usuário autenticado
        $authUser = Auth::user();

        // Perfil baseado no usuário autenticado
        $profile = ProfileHelper::getUserProfileByRole($authUser);

        foreach($data['soft_skills'] as $softSkill) {
            // Armazeno a definição de cada soft skill
            SoftSkill::findOrFail($softSkill['soft_skill_id']);

            CompanySoftSkill::create([
                'soft_skill_id' => $softSkill['soft_skill_id'],
                'company_profile_id' => $profile->id
            ]);

        }

        $profile->refresh();

        return CompanySoftSkillResource::collection($profile->company_soft_skills);
    }

    public function updateCompanySoftSkills(array $data) {

        $authUser = Auth::user();

        $profile = ProfileHelper::getUserProfileByRole($authUser);

        foreach($data['soft_skills'] as $softSkill) {

            $item = CompanySoftSkill::where('id', $data['id']);

            $item->update([
                'soft_skill_id' => $softSkill['soft_skill_id']
            ]);

        }

        $profile->refresh();

        return CompanySoftSkillResource::collection($profile->company_soft_skills);

    }

    public function destroyCompanySoftSkills(CompanySoftSkill $companySoft) {

        $authUser = Auth::user();
        $profile = ProfileHelper::getUserProfileByRole($authUser);

        if($companySoft->company_profile_id !== $profile->id) {
            throw new AuthorizationException();
        }

        return $companySoft->where('id', $companySoft->id)->delete();

    }

    public function indexCompanySoftSkills() {

        $authUser = Auth::user();
        $profile = ProfileHelper::getUserProfileByRole($authUser);

        return CompanySoftSkill::query()->where('company_profile_id', $profile->id)->get();

    }

}
