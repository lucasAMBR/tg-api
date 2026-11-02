<?php

use App\Enums\TranslationStatusEnum;
use App\Models\Comment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'posts',
        'comments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->text('content_pt')->nullable()->after('content');
                $table->text('content_en')->nullable()->after('content_pt');
                $table->string('translation_status')->default(TranslationStatusEnum::PENDING->value);
            });
        }

        // Comentário já removido não tem o que traduzir: o texto é o marcador em todos os idiomas.
        DB::table('comments')->whereNotNull('removed_at')->update([
            'content_pt' => Comment::REMOVED_CONTENT,
            'content_en' => Comment::REMOVED_CONTENT,
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['content_pt', 'content_en', 'translation_status']);
            });
        }
    }
};
