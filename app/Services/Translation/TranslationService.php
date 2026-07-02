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
            Log::error('Erro na tradução', [
                'status' => $request->status(),
                'body' => $request->body(),
            ]);
            throw new ApiException('Erro ao detectar o idioma', 500, $request->json());
        }

        Log::info('Detectado o idioma', ['language' => $request->json('language')]);

        return $request->json('language');
    }

    public function translate(string $content, string $source, string $target)
    {
        Log::info('Traduzindo o conteúdo', ['content' => $content, 'source' => $source, 'target' => $target]);

        $request = Http::post(config('app.services.translation.url'), [
            'q' => $content,
            'source' => $source,
            'target' => $target,
        ]);

        if ($request->failed()) {
            Log::error('Erro na tradução', [
                'status' => $request->status(),
                'body' => $request->body(),
            ]);
            throw new ApiException('Erro ao traduzir o conteúdo', 500, $request->json());
        }

        Log::info('Tradução concluída', ['translation' => $request->json('translatedText')]);

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

    public function translateBatch(array $data): array
    {
        $translatedData = [];
        
        foreach ($data as $field => $value) {
            $language = $this->detectLanguage($value);

            if ($language === 'pt') {
                $translatedData[$field]['pt'] = $value;
            }else{
                $translatedData[$field]['pt'] = $this->translate($value, $language, 'pt');
            }

            if ($language === 'en') {
                $translatedData[$field]['en'] = $value;
            }else{
                $translatedData[$field]['en'] = $this->translate($value, $language, 'en');
            }
        }

        return $translatedData;
    }
}
