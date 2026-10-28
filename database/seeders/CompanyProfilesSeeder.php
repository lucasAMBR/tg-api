<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\Language;
use App\Models\SoftSkill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Popula 12 empresas completas (bio, endereço, projetos, stacks e soft skills valorizadas)
 * a partir de database/seeders/data/companies.json.
 *
 * Os perfis cobrem segmentos operacionais e stacks diferentes, para que a listagem de empresas
 * e as recomendações tenham variedade suficiente de cenários.
 */
class CompanyProfilesSeeder extends Seeder
{
    private const PASSWORD = 'user1234!';

    /** @var Collection<string, string> slug da linguagem => id */
    private Collection $languageIds;

    /** @var Collection<string, string> nome da soft skill => id */
    private Collection $softSkillIds;

    public function run(): void
    {
        $companies = $this->loadCompanies();

        if (!$companies) {
            return;
        }

        $this->languageIds = Language::pluck('id', 'slug');
        $this->softSkillIds = SoftSkill::pluck('id', 'name');

        foreach ($companies as $index => $data) {
            DB::transaction(fn () => $this->seedCompany($data, $index));
        }
    }

    private function loadCompanies(): ?array
    {
        $path = database_path('seeders/data/companies.json');

        if (!file_exists($path)) {
            $this->command->warn("Arquivo não encontrado: {$path}");
            return null;
        }

        $companies = json_decode(file_get_contents($path), true);

        if (!is_array($companies) || $companies === []) {
            $this->command->warn('companies.json inválido ou vazio.');
            return null;
        }

        return $companies;
    }

    private function seedCompany(array $data, int $index): CompanyProfile
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole('company')) {
            $user->assignRole('company');
        }

        $companyProfile = CompanyProfile::updateOrCreate(
            ['user_id' => $user->id],
            [...$data['profile'], 'cnpj' => $this->fakeCnpj($index)]
        );

        // O seeder é re-executável: os filhos são recriados do zero para não duplicar.
        $this->clearRelations($companyProfile);

        $this->seedAddress($companyProfile, $data['address']);
        $this->seedProjects($companyProfile, $data['projects'] ?? []);
        $this->seedSoftSkills($companyProfile, $data['soft_skills'] ?? []);

        $companyProfile->languages()->sync($this->resolveLanguageIds($data['languages'] ?? []));

        return $companyProfile;
    }

    private function clearRelations(CompanyProfile $companyProfile): void
    {
        $companyProfile->company_projects()->forceDelete();
        $companyProfile->company_soft_skills()->forceDelete();
    }

    private function seedAddress(CompanyProfile $companyProfile, array $address): void
    {
        // Criado pela relação morph para que addressable_id e addressable_type sejam preenchidos,
        // já que não estão no fillable do Address.
        $companyProfile->address()->forceDelete();
        $companyProfile->address()->create($address);
    }

    private function seedProjects(CompanyProfile $companyProfile, array $projects): void
    {
        foreach ($projects as $project) {
            $companyProject = $companyProfile->company_projects()->create([
                'title' => $project['title'],
                'description' => $project['description'],
            ]);

            $companyProject->languages()->sync($this->resolveLanguageIds($project['languages'] ?? []));
        }
    }

    private function seedSoftSkills(CompanyProfile $companyProfile, array $softSkills): void
    {
        foreach ($softSkills as $softSkill) {
            $softSkillId = $this->softSkillIds[$softSkill] ?? null;

            if (!$softSkillId) {
                $this->command->warn("Soft skill não encontrada: {$softSkill}");
                continue;
            }

            $companyProfile->company_soft_skills()->create(['soft_skill_id' => $softSkillId]);
        }
    }

    /**
     * @param array<int, string> $slugs
     * @return array<int, string>
     */
    private function resolveLanguageIds(array $slugs): array
    {
        return collect($slugs)
            ->map(fn (string $slug) => $this->languageIds[$slug] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * CNPJ sintético com dígitos verificadores válidos, para não repetir o mesmo número em todas as empresas.
     */
    private function fakeCnpj(int $seed): string
    {
        $digits = str_split(str_pad((string) (10000000000 + ($seed * 7919) % 80000000000), 12, '0', STR_PAD_LEFT));

        foreach ([5, 6] as $offset => $start) {
            $sum = 0;
            $weight = $start;

            foreach (array_slice($digits, 0, 12 + $offset) as $digit) {
                $sum += ((int) $digit) * $weight;
                $weight = $weight === 2 ? 9 : $weight - 1;
            }

            $remainder = $sum % 11;
            $digits[12 + $offset] = (string) ($remainder < 2 ? 0 : 11 - $remainder);
        }

        return implode('', $digits);
    }
}
