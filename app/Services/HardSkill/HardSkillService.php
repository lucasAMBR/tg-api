<?php

namespace App\Services\HardSkill;

use App\Helpers\ProfileHelper;
use App\Http\Resources\HardSkill\HardSkillCollection;
use App\Http\Resources\HardSkill\HardSkillResource;
use App\Models\HardSkill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HardSkillService
{
    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $search = $data['search'] ?? null;

        $dev_profile_id = $data['dev_profile_id'] ?? null;

        $hardSkills = HardSkill::query()
            ->when($dev_profile_id, function (Builder $q) use ($dev_profile_id) {
                $q->where('dev_profile_id', $dev_profile_id);
            })
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('skill_level', 'ILIKE', "%{$search}%")
                        ->orWhereHas('language', function ($subSubQuery) use ($search) {
                            $subSubQuery->where('name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new HardSkillCollection($hardSkills);
    }

    public function store(array $data): HardSkillResource
    {
        $authUser = Auth::user();

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function () use ($devProfile, $data): HardSkillResource {
            $hardSkill = HardSkill::create([
                'language_id' => $data['language_id'],
                'skill_level' => $data['skill_level'],
                'dev_profile_id' => $devProfile->id
            ]);

            return new HardSkillResource($hardSkill);
        });
    }

    public function update(HardSkill $hardSkill, array $data): HardSkillResource
    {
        return DB::transaction(function () use ($data, $hardSkill): HardSkillResource {
            $hardSkill->update($data);

            $hardSkill->refresh();

            return new HardSkillResource($hardSkill);
        });
    }

    public function delete(HardSkill $hardSkill): void
    {
        DB::transaction(function () use ($hardSkill): void {
            $hardSkill->delete();
        });
    }
}
