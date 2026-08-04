<?php 

namespace App\Services\DevJobVacancy;

use App\Enums\JobVacancyStatusEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevJobVacancy\DevJobVacancyCollection;
use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Models\DevJobVacancy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevJobVacancyService {

    public function apply(array $data): DevJobVacancyResource {

        $authUser = Auth::user();

        // Verifica a role
        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't apply for this vacancy!");
        }

        // Carrega o perfil do usuário
        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        if (!$devProfile) {
            throw new ApiException("Developer profile not found!");
        }

        $vacancy = DevJobVacancy::where('dev_profile_id', $devProfile->id)
            ->where('job_vacancy_id', $data['job_vacancy_id'])
            ->exists();
            
        // Valida se o usuário ja esta inscrito nessa vaga
        if($vacancy) {
            throw new ApiException('Developer already applied for this vacancy!');
        }

        return DB::transaction(function () use ($devProfile, $data) {

            $application = DevJobVacancy::create([
                'dev_profile_id' => $devProfile->id,
                'job_vacancy_id' => $data['job_vacancy_id'],
                'status' => JobVacancyStatusEnum::PENDING,
                'feedback' => $data['feedback'] ?? null,
            ]);

            $application->refresh();
            $application->load(['jobVacancy', 'devProfile']);

            return new DevJobVacancyResource($application);

        });

    }

    public function indexApplies(array $data): DevJobVacancyCollection {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't index applies for this vacancy!");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $page = $data['page'] ?? 1;
        $per_page = $data['per_page'] ?? 10;
        $search = $data['search'] ?? '';

        $applies = DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
        ->whereHas('jobVacancy', function($query) use ($companyProfile) {
            $query->where('company_profile_id', $companyProfile->id);
        })
        ->when($search, function($query) use ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('devProfile', function($devQuery) use ($search) {
                    $devQuery->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('jobVacancy', function($companyQuery) use ($search) {
                    $companyQuery->where('title', 'ILIKE', "%{$search}%");
                });
            });
        })->paginate(
            $per_page,
            ['*'],
            'page',
            $page
        );

        return new DevJobVacancyCollection($applies);

    }

    public function reviewApply(array $data): DevJobVacancyResource {

        $authUser = Auth::user();
        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $apply = DevJobVacancy::query()->with(['jobVacancy', 'devProfile'])
            ->where('id', $data['id'])
            ->whereHas('jobVacancy', function($query) use ($companyProfile) {
                $query->where('company_profile_id', $companyProfile->id);
            })->first();

        if(!$apply) {
            throw new ApiException("This apply does not belong to your company!", 403);
        }

        return DB::transaction(function() use ($apply, $data) {

            $apply->update([
                'status' => $data['status']
            ]);

            return new DevJobVacancyResource($apply);

        });

    }

}