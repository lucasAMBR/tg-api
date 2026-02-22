<?php

namespace App\Services\Languages;

use App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LanguageService {

    public function store(Array $data) {

        $authUser = Auth::user();

        DB::transaction(function() use ($data, $authUser) {

            $language = Language::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'user_id' => $authUser->id, // Ja puxa o id do user logado automaticamente pro banco
                'is_oficial' => $data['is_oficial'] ?? false, // Se não passar nenhum valor fica como false
                'is_approved' => $data['is_approved'] ?? false,
            ]);

            return $language;

        });
    }

}