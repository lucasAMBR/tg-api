<?php 

namespace App\Services\DevJobVacancy;

use App\Enums\JobVacancyStatusEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Models\DevJobVacancy;
use App\Models\JobVacancy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevJobVacancyService {

    public function apply(array $data) {

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
                'feedback' => $data['feedback'],
            ]);

            $application->refresh();
            return $application->load(['jobVacancy', 'devProfile']);

        });

    }

}