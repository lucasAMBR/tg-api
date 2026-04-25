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
            [
                'name' => 'TypeScript',
                'slug' => 'typescript',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Python',
                'slug' => 'python',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'C#',
                'slug' => 'csharp',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Go',
                'slug' => 'go',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Rust',
                'slug' => 'rust',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Kotlin',
                'slug' => 'kotlin',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Spring Boot',
                'slug' => 'spring-boot',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'React',
                'slug' => 'react',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Next.js',
                'slug' => 'next-js',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'C',
                'slug' => 'c',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'C++',
                'slug' => 'cpp',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Visual Basic',
                'slug' => 'visual-basic',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Delphi',
                'slug' => 'delphi',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Cobol',
                'slug' => 'cobol',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Ruby',
                'slug' => 'ruby',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Ruby on Rails',
                'slug' => 'ruby-on-rails',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Django',
                'slug' => 'django',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Flask',
                'slug' => 'flask',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'ASP.NET',
                'slug' => 'aspnet',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'ASP.NET Core',
                'slug' => 'aspnet-core',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Angular',
                'slug' => 'angular',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Vue.js',
                'slug' => 'vue-js',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Svelte',
                'slug' => 'svelte',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'jQuery',
                'slug' => 'jquery',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Bootstrap',
                'slug' => 'bootstrap',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Tailwind CSS',
                'slug' => 'tailwind-css',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Flutter',
                'slug' => 'flutter',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'React Native',
                'slug' => 'react-native',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'Electron',
                'slug' => 'electron',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'HTML',
                'slug' => 'html',
                'is_approved' => true,
                'is_official' => true
            ],
            [
                'name' => 'CSS',
                'slug' => 'css',
                'is_approved' => true,
                'is_official' => true
            ],
        ]);

        /**
         * O método insert() não gera uuid, insere direto no banco,
         * para não dar problema com uuid melhor usar o create()
         */
        foreach ($languages as $language) {
            Language::firstOrCreate(
                ['slug' => $language['slug']],
                $language
            );
        }

    }
}
