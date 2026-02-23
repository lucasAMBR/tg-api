<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $languages = ([
            [
                'name' => 'Java',
                'slug' => 'java',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'JavaScript',
                'slug' => 'java-script',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'PHP',
                'slug' => 'php',
                'is_approved' => true,
                'is_official' => true
            ],
        ]);

        /**
         * O método insert() não gera uuid, insere direto no banco,
         * para não dar problema com uuid melhor usar o create()
         */
        foreach($languages as $language) {
            Language::create($language);
        }

    }
}
