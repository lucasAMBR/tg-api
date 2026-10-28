<?php

use App\Enums\DevJobVacancyStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Valores antigos do status da candidatura e seus equivalentes atuais
     */
    private const STATUS_MAP = [
        'pending' => DevJobVacancyStatusEnum::IN_PROGRESS,
        'refusal' => DevJobVacancyStatusEnum::REJECTED,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::STATUS_MAP as $oldStatus => $newStatus) {
            DB::table('dev_job_vacancy')
                ->where('status', $oldStatus)
                ->update(['status' => $newStatus->value]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::STATUS_MAP as $oldStatus => $newStatus) {
            DB::table('dev_job_vacancy')
                ->where('status', $newStatus->value)
                ->update(['status' => $oldStatus]);
        }
    }
};
