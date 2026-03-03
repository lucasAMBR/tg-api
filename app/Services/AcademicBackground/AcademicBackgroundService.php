<?php

namespace App\Services\AcademicBackground;

use App\Helpers\ProfileHelper;
use App\Http\Resources\AcademicBackground\AcademicBackgroundCollection;
use App\Http\Resources\AcademicBackground\AcademicBackgroundResource;
use App\Models\AcademicBackground;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AcademicBackgroundService
{
    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;

        $dev_profile_id = $data['dev_profile_id'] ?? null;
        $verified = $data['verified'] ?? false;

        $academicBackgrounds = AcademicBackground::query()
            ->when($dev_profile_id, function (Builder $q, $dev_profile_id) {
                $q->where('dev_profile_id', $dev_profile_id);
            })
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('degree', 'ILIKE', "%{$search}%")
                        ->orWhere('degree_level', 'ILIKE', "%{$search}%")
                        ->orWhere('institution', 'ILIKE', "%{$search}%");
                });
            })
            ->when($verified === true, function (Builder $q) {
                $q->whereHas('media');
            })->paginate($perPage, ['*'], 'page', $page);

        return new AcademicBackgroundCollection($academicBackgrounds);
    }

    public function store(array $data)
    {
        $authUser = Auth::user();

        $dev_profile = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function () use ($data, $dev_profile) {
            $academicBackground = AcademicBackground::create([
                'degree' => $data['degree'],
                'degree_level'=> $data['degree_level'],
                'institution' => $data['institution'],
                'dev_profile_id' => $dev_profile->id
            ]);

            if(isset($data['certificate'])){
                $academicBackground->addMedia($data['certificate'])
                    ->toMediaCollection('certificate');
            }

            return new AcademicBackgroundResource($academicBackground);
        });
    }

    public function update(AcademicBackground $academicBackground, array $data)
    {
        return DB::transaction(function () use ($academicBackground, $data) {
            $academicBackground->update($data);

            if(isset($data['certificate'])){
                $academicBackground->addMedia($data['certificate'])
                    ->toMediaCollection('certificate');
            }

            return new AcademicBackgroundResource($academicBackground);
        });
    }

    public function delete(AcademicBackground $academicBackground)
    {
        DB::transaction(function () use ($academicBackground) {
            $academicBackground->delete();
        });
    }
}
