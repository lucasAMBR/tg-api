<?php

namespace App\Services\Translation;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{

    public function detectLanguage(string $content) {
        $request = Http::post(config('app.services.translation.detection_url'), [
            'text' => $content,
        ]);

        if ($request->failed()) {
            throw new ApiException('Erro ao detectar o idioma', 500, $request->json());
        }

        return $request->json('language');
    }

    public function translate(string $content, string $source, string $target)
    {
        $request = Http::post(config('app.services.translation.url'), [
            'q' => $content,
            'source' => $source,
            'target' => $target,
        ]);

        if ($request->failed()) {
            throw new ApiException('Erro ao traduzir o conteúdo', 500, $request->json());
        }

        return $request->json('translatedText');
    }

    public function getPortugueseAndEnglishTranslation(string $content)
    {

        $language = $this->detectLanguage($content);

        if ($language === 'pt') {
            $portugueseTranslation = $content;
        }else{
            $portugueseTranslation = $this->translate($content, $language, 'pt');
        }

        if ($language === 'en') {
            $englishTranslation = $content;
        }else{
            $englishTranslation = $this->translate($content, $language, 'en');
        }

        return [
            'pt-br' => $portugueseTranslation,
            'en' => $englishTranslation
        ];
    }
}
