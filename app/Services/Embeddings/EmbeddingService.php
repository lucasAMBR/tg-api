<?php

namespace App\Services\Embeddings;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    private const TIMEOUT_SECONDS = 60;

    private const RETRY_TIMES = 3;

    private const RETRY_DELAY_MS = 2000;

    /**
     * Retorna o vetor do embedding, já extraído da resposta da OpenAI.
     *
     * @return array<int, float>
     *
     * @throws ApiException quando a OpenAI responde com erro após todas as tentativas
     *                      ou devolve um corpo sem o vetor.
     */
    public function generate(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('app.services.embedding.key'),
            'Content-Type' => 'application/json',
        ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(self::RETRY_TIMES, self::RETRY_DELAY_MS, throw: false)
            ->post(config('app.services.embedding.url'), [
                'model' => config('app.services.embedding.model'),
                'input' => $text
            ]);

        if ($response->failed()) {
            throw new ApiException(
                message: 'Não foi possível gerar o embedding.',
                data: ['erro' => $response->body()]
            );
        }

        $vector = $response->json('data.0.embedding');

        if (!is_array($vector) || $vector === []) {
            throw new ApiException(
                message: 'A resposta do embedding não contém o vetor.',
                data: ['erro' => $response->body()]
            );
        }

        return $vector;
    }
}
