<?php

namespace App\Services\CompanyProjects;

use App\Helpers\ProfileHelper;
use App\Http\Resources\CompanyProject\CompanyProjectResource;
use App\Models\CompanyProject;
use App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyProjectService {

    public function store(array $data): CompanyProjectResource {

        $authUser = Auth::user();

        /**
         * Puxa o profile baseado na role do usuário logado
         */
        $company = ProfileHelper::getUserProfileByRole($authUser);

        return DB::transaction(function() use ($data, $company) {
        
            $companyProject = CompanyProject::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'company_profile_id' => $company->id,
            ]);

            /**
             * Faz a sincronização com a tabela intermediária 
             * a partir do campo passado na request
             */
            $companyProject->languages()->sync($data['languages']);
            $companyProject->load(['languages']);
            
            return new CompanyProjectResource($companyProject);

        });
    }

    // public function update(CompanyProject $companyProject, array $data): CompanyProjectResource {

    //     DB::transaction(function() use ($companyProject, $data) {

    //         $companyProject->update($data);

    //         if(isset($data['languages'])) {
    //             $companyProject->languages()->sync($data['languages']);
    //         }

    //         return new CompanyProjectResource($companyProject); 

    //     });

    // }

}