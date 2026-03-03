<?php

namespace App\Http\Controllers\Embedding;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Embeddings\EmbeddingService;
use Illuminate\Http\Request;

class EmbeddingController extends Controller
{
    public function __construct(protected EmbeddingService $embeddingService){}

    public function generate(Request $request){
        $data = $request->all();

        $embedding = $this->embeddingService->generate($data['text']);

        return ApiResponse::success($embedding);
    }
}
