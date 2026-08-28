<?php

use App\Enums\SelectionProcessStageEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dev_job_vacancy', function (Blueprint $table) {
            $table->string('process_step')
                ->default(SelectionProcessStageEnum::AWAITING_RESUME_SCREENING->value)
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dev_job_vacancy', function (Blueprint $table) {
            $table->dropColumn('process_step');
        });
    }
};
