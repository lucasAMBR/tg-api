<?php

namespace App\Services\Embeddings;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function generate(string $text)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPEN_IA_KEY'),
            'Content-Type' => 'application/json',
        ])->post(env("OPEN_IA_URL"), [
            'model' => 'text-embedding-3-small',
            'input' => $text
        ]);

        if ($response->failed()) {
            return response()->json([
                'erro' => $response->body()
            ], 500);
        }

        $embedding = $response->json();

        return $embedding;
    }
}
