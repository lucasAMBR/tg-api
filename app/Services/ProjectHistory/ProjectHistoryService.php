<?php

namespace App\Services\ProjectHistory;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\ProjectHistory\ProjectHistoryCollection;
use App\Http\Resources\ProjectHistory\ProjectHistoryResource;
use App\Models\ProjectHistory;
use App\Models\ProjectHistoryImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectHistoryService
{
    public function index(array $data){
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;

        $profile_id = $data['dev_profile_id'] ?? null;

        $projects = ProjectHistory::query()
            ->when($profile_id, function (Builder $query, $profile_id) {
                $query->where('dev_profile_id', $profile_id);
            })
            ->when($search, function (Builder $query, $search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('title', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%")
                        ->orWhereHas('languages', function ($q) use ($search) {
                            $q->where('name', "ILIKE", "%{$search}%");
                        });
                });
            })
            ->with('languages')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ProjectHistoryCollection($projects);
    }

    public function store(array $data): ProjectHistoryResource
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

    public function update(ProjectHistory $projectHistory, array $data): ProjectHistoryResource
    {
        return DB::transaction(function () use ($projectHistory, $data) {
            $projectHistory->update($data);

            if(isset($data['languages'])){
                $projectHistory->languages()->sync($data['languages']);
            }

            return new ProjectHistoryResource($projectHistory);
        });
    }

    public function delete(ProjectHistory $projectHistory): void
    {
        DB::transaction(function () use ($projectHistory) {
            $projectHistory->delete();
        });
    }

    public function saveImagesInProject(ProjectHistory $projectHistory, array $images): ProjectHistoryResource
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

    public function removeImageFromProject(ProjectHistory $projectHistory, int $mediaId):void
    {
        $media = Media::findOrFail($mediaId);

        if($projectHistory->id != $media->model_id){
            throw new ApiException("This image doesn't belong to this project galery!");
        }

        $media->delete();
    }
}
