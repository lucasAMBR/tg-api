<?php

namespace App\Services\CompanyProjects;

use App\Helpers\ProfileHelper;
use App\Http\Resources\CompanyProject\CompanyProjectCollection;
use App\Http\Resources\CompanyProject\CompanyProjectResource;
use App\Models\CompanyProject;
use Illuminate\Database\Eloquent\Builder;
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
                'prod_url' => $data['prod_url'] ?? null,
                'github_url' => $data['github_url'] ?? null,
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

    public function update(array $data, CompanyProject $companyProject): CompanyProjectResource {

        // Lembrete pra mim (Kauã) nunca mais esquecer o caralho do return 
        return DB::transaction(function() use ($data, $companyProject) {

            $companyProject->update($data);

            if(isset($data['languages'])) {
                $companyProject->languages()->sync($data['languages']);
            }

            return new CompanyProjectResource($companyProject); 

        });
        
    }

    public function destroy(CompanyProject $companyProject): CompanyProjectResource {

        return DB::transaction(function() use ($companyProject) {

            $companyProject->delete();

            return new CompanyProjectResource($companyProject);

        });

    }

    public function index(array $data): CompanyProjectCollection {

        /**
         * Armazeno nas variáveis valores padrões de paginação
         */
        $page = $data['page'] ?? 1;
        $per_page = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;

        /**
         * Armazeno o id do profile da empresa baseado no que foi passado
         * via request
         */
        $company_id = $data['company_profile_id'] ?? null;

        /**
         * Faço a query personalizada exibindo o title, description,
         * as languages e os names além da regra de paginnação passadas como
         * parâmetros
         * Além de verificar se o company_id é nulo por causa do isset
         */
        $companyProject = CompanyProject::query()
            ->when(isset($company_id), function(Builder $query) use ($company_id) {
                $query->where('company_profile_id', $company_id);
            })
            ->when($search, function(Builder $query, $search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('title', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%")
                        ->orWhereHas('languages', function($q) use ($search) {
                            $q->where('name', 'ILIKE', "%{$search}%");
                    });
                });
            })
            ->with('languages')
            ->paginate($per_page, ['*'], 'page', $page);

            // dd($companyProject);
            return new CompanyProjectCollection($companyProject);
    }

}