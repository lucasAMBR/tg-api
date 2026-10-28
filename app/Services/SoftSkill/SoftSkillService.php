<?php

namespace App\Services\SoftSkill;

use App\Enums\SeniorityLevelEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\CompanySoftSkill\CompanySoftSkillResource;
use App\Http\Resources\DevSoftSkill\DevSoftSkillResource;
use App\Http\Resources\SoftSkill\SoftSkillResource;
use App\Jobs\GenerateDevProfileEmbeddingJob;
use App\Models\CompanySoftSkill;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\DevSoftSkill;
use App\Models\SoftSkill;
use App\Models\SoftSkillLevelResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        GenerateDevProfileEmbeddingJob::dispatchDebounced($profile->id);

        $profile->load('dev_soft_skills.soft_skill', 'dev_soft_skills.soft_skill_level_response');

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

        GenerateDevProfileEmbeddingJob::dispatchDebounced($profile->id);

        $profile->load('dev_soft_skills.soft_skill', 'dev_soft_skills.soft_skill_level_response');

        return DevSoftSkillResource::collection($profile->dev_soft_skills);
    }

    public function getDevSoftSkillsByProfileId(array $data){
        $devProfile = DevProfile::findOrFail($data['dev_profile_id']);

        $softSkills = DevSoftSkill::query()
            ->with(['soft_skill', 'soft_skill_level_response'])
            ->select('dev_soft_skill.*')
            ->join('soft_skill_level_responses', 'dev_soft_skill.soft_skill_level_response_id', '=', 'soft_skill_level_responses.id')
            ->where('dev_soft_skill.dev_profile_id', $devProfile->id)
            ->orderBy('soft_skill_level_responses.evaluation_weight', 'desc')
            ->get();

        return DevSoftSkillResource::collection($softSkills);
    }

    public function getPointLimitBasedOnSeniorityLevel(DevProfile $profile): int
    {
        $limits = SeniorityLevelEnum::softSkillsPointLimit();

        return $limits[$profile->seniority_level] ?? 25;
    }

    public function syncCompanySoftSkills(array $data)
    {
        $company = CompanyProfile::findOrFail($data['company_profile_id']);

        $authUser = Auth::user();

        if($authUser->id !== $company->user_id){
            throw new ApiException("You cannot sync someone else's company soft skills!", 403);
        }

        return DB::transaction(function () use ($company, $data) {
            $softSkillIds = collect($data['soft_skills'])
                ->unique()
                ->values()
                ->all();

            CompanySoftSkill::where('company_profile_id', $company->id)->delete();

            foreach($softSkillIds as $softSkillId) {
                CompanySoftSkill::create([
                    'soft_skill_id' => $softSkillId,
                    'company_profile_id' => $company->id,
                ]);
            }

            return CompanySoftSkillResource::collection(
                CompanySoftSkill::with('soft_skills')->where('company_profile_id', $company->id)->get()
            );
        });
    }

    public function indexCompanySoftSkills(array $data)
    {
        $company = CompanyProfile::findOrFail($data['company_profile_id']);

        return CompanySoftSkillResource::collection(
            CompanySoftSkill::with('soft_skills')->where('company_profile_id', $company->id)->get()
        );
    }

}
