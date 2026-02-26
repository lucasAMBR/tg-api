<?php

namespace App\Services\ProjectHistory;

use App\Helpers\ProfileHelper;
use App\Http\Resources\ProjectHistory\ProjectHistoryResource;
use App\Models\ProjectHistory;
use App\Models\ProjectHistoryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;

class ProjectHistoryService
{
    public function store(array $data)
    {
        $authUser = Auth::user();

        $profile = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function () use ($data, $profile) {
            $projectHistory = ProjectHistory::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'dev_profile_id' => $profile->id
            ]);

            $projectHistory->languages()->sync($data['languages']);

            $projectHistory->load(['languages']);

            return new ProjectHistoryResource($projectHistory);
        });
    }

    public function saveImagesInProject(ProjectHistory $projectHistory, array $images)
    {
        $images = Arr::flatten($images);

        foreach($images as $image){
            if($image instanceof UploadedFile){
                $projectHistory->addMedia($image)
                    ->toMediaCollection('gallery');
            }
        }

        return new ProjectHistoryResource($projectHistory);
    }

}
