<?php

namespace App\Services\EmploymentHistory;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\EmploymentHistory\EmploymentHistoryCollection;
use App\Http\Resources\EmploymentHistory\EmploymentHistoryResource;
use App\Models\EmploymentHistory;
use App\Services\Translation\TranslationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmploymentHistoryService
{
    public function __construct(private TranslationService $translationService){}

    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;

        $profile_id = $data['profile_id'] ?? null;

        $employmentHistory = EmploymentHistory::query()
            ->when($profile_id, function (Builder $query, $profile_id) {
                $query->where('dev_profile_id', $profile_id);
            })
            ->when($search, function (Builder $query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('position_name', "ILIKE", "%{$search}%")
                        ->orWhere('company_location', "ILIKE", "%{$search}%")
                        ->orWhere('company_name', "ILIKE", "%{$search}%");
                });
            })
            ->orderBy('start_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return new EmploymentHistoryCollection($employmentHistory);
    }

    public function store(array $data)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if($authUser->hasRole('admin') && $authUser->admin_active_profile != "dev"){
            throw new ApiException('Your actual active profile is not a dev profile, please change before to try again!');
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function () use ($data, $devProfile) {
            $positionNameTranslations = $this->translationService->getPortugueseAndEnglishTranslation($data['position_name']);
            $actuationDetailsTranslations = $this->translationService->getPortugueseAndEnglishTranslation($data['actuation_details']);

            $employmentHistory = EmploymentHistory::create([
                'company_name' => $data['company_name'],
                'company_location' => $data['company_location'],
                'position_name' => $data['position_name'],
                'position_name_pt' => $positionNameTranslations['pt-br'],
                'position_name_en' => $positionNameTranslations['en'],
                'employment_type' => $data['employment_type'],
                'contract_type' => $data['contract_type'],
                'seniority_level' => $data['seniority_level'],
                'actuation_details' => $data['actuation_details'],
                'actuation_details_pt' => $actuationDetailsTranslations['pt-br'],
                'actuation_details_en' => $actuationDetailsTranslations['en'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'is_current' => $data['is_current'],
                'dev_profile_id' => $devProfile->id
            ]);

            return new EmploymentHistoryResource($employmentHistory);
        });
    }

    public function update(EmploymentHistory $employmentHistory, array $data)
    {
        return DB::transaction(function () use ($employmentHistory, $data) {
            $employmentHistory->update($data);

            if(isset($data['position_name'])){
                $translations = $this->translationService->getPortugueseAndEnglishTranslation($data['position_name']);
                $employmentHistory->update([
                    'position_name_pt' => $translations['pt-br'],
                    'position_name_en' => $translations['en'],
                ]);
            }

            if(isset($data['actuation_details'])){
                $translations = $this->translationService->getPortugueseAndEnglishTranslation($data['actuation_details']);
                $employmentHistory->update([
                    'actuation_details_pt' => $translations['pt-br'],
                    'actuation_details_en' => $translations['en'],
                ]);
            }

            return new EmploymentHistoryResource($employmentHistory);
        });
    }

    public function delete(EmploymentHistory $employmentHistory)
    {
        DB::transaction(function () use ($employmentHistory) {
            $employmentHistory->delete();
        });
    }
}
