<?php

use App\Enums\DevSpecialtyEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('job_vacancies')
            ->whereNotNull('specialties')
            ->orderBy('id')
            ->chunkById(100, function ($jobVacancies) {
                foreach ($jobVacancies as $jobVacancy) {
                    DB::table('job_vacancies')
                        ->where('id', $jobVacancy->id)
                        ->update(['specialties' => $this->normalizeSpecialty($jobVacancy->specialties)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    /**
     * Converte o valor antigo (array em JSON) para um único valor do enum,
     * mantendo o primeiro item que corresponde a uma especialidade válida
     */
    private function normalizeSpecialty(string $specialties): ?string
    {
        $decoded = json_decode($specialties, true);

        $candidates = is_array($decoded) ? $decoded : [$specialties];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && DevSpecialtyEnum::tryFrom($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
};
