<?php

namespace App\Services\Languages;

use App\Http\Resources\Language\LanguageCollection;
use App\Http\Resources\Language\LanguageResource;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LanguageService {

    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;

        $languages = Language::query()
            ->where('is_approved', true)
            ->when($search, function (Builder $query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                });
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new LanguageCollection($languages);
    }

    public function store(Array $data) {

        return DB::transaction(function() use ($data) {
            $language = Language::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'is_official' => $data['is_oficial'] ?? false,
                'is_approved' => $data['is_approved'] ?? false,
            ]);

            return new LanguageResource($language);
        });
    }

    public function update(Language $language, array $data)
    {
        return DB::transaction(function () use ($data, $language) {
            $language->update($data);

            return new LanguageResource($language);
        });
    }

    public function delete(Language $language)
    {
        DB::transaction(function () use ($language) {
            $language->delete();
        });
    }

    public function approveLanguage(Language $language)
    {
        return DB::transaction(function () use ($language) {
            $language->update([
                'is_approved' => true
            ]);

            return new LanguageResource($language);
        });
    }
}
