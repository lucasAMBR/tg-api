<?php

namespace App\Services\AdditionalCourse;

use App\Helpers\ProfileHelper;
use App\Http\Resources\AdditionalCourse\AdditionalCourseCollection;
use App\Http\Resources\AdditionalCourse\AdditionalCourseResource;
use App\Models\AdditionalCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdditionalCourseService
{
    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $search = $data['search'] ?? null;

        $dev_profile_id = $data['dev_profile_id'] ?? null;
        $verified = $data['verified'] ?? false;

        $additionalCourses = AdditionalCourse::query()
            ->when($dev_profile_id, function (Builder $q) use ($dev_profile_id) {
                $q->where('dev_profile_id', $dev_profile_id);
            })
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('provider', 'like', "%{$search}%");
                });
            })
            ->when($verified === true, function (Builder $q){
                $q->whereHas('media');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new AdditionalCourseCollection($additionalCourses);
    }

    public function store(array $data)
    {
        $authUser = Auth::user();

        $dev_profile = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function () use ($data, $dev_profile) {
            $additionalCourse = AdditionalCourse::create([
                'name' => $data['name'],
                'provider' => $data['provider'],
                'dev_profile_id' => $dev_profile->id
            ]);

            if(isset($data['certificate'])){
                $additionalCourse->addMedia($data['certificate'])
                    ->toMediaCollection('certificate');
            }

            return new AdditionalCourseResource($additionalCourse);
        });
    }

    public function update(AdditionalCourse $additionalCourse, array $data)
    {
        return DB::transaction(function () use ($additionalCourse, $data) {
            $additionalCourse->update($data);

            if(isset($data['certificate'])){
                $additionalCourse->addMedia($data['certificate'])
                    ->toMediaCollection('certificate');
            }

            return new AdditionalCourseResource($additionalCourse);
        });
    }

    public function delete(AdditionalCourse $additionalCourse)
    {
        DB::transaction(function () use ($additionalCourse) {
            $additionalCourse->delete();
        });
    }
}
