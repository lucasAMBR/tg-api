<?php

namespace App\Console\Commands;

use App\Exceptions\ApiException;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;

class TranslateQuestionsCommand extends Command
{
    protected $signature = 'questions:translate
                            {--force : Retraduzir mesmo quando as colunas já estão preenchidas}
                            {--questions-only : Traduzir apenas perguntas}
                            {--responses-only : Traduzir apenas respostas}';

    protected $description = 'Traduz perguntas e respostas para português e inglês usando o microserviço de tradução';

    public function __construct(private TranslationService $translationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $questionsOnly = (bool) $this->option('questions-only');
        $responsesOnly = (bool) $this->option('responses-only');

        if ($questionsOnly && $responsesOnly) {
            $this->error('Use apenas uma das opções: --questions-only ou --responses-only.');
            return self::FAILURE;
        }

        $translatedQuestions = 0;
        $translatedResponses = 0;
        $errors = 0;

        if (!$responsesOnly) {
            $this->info('Traduzindo perguntas...');

            Question::query()
                ->when(!$force, fn ($query) => $query->where(fn ($q) => $q->whereNull('question_pt')->orWhereNull('question_en')))
                ->chunkById(50, function ($questions) use ($force, &$translatedQuestions, &$errors) {
                    foreach ($questions as $question) {
                        $result = $this->translateQuestion($question, $force);

                        if ($result === true) {
                            $translatedQuestions++;
                        } elseif ($result === false) {
                            $errors++;
                        }
                    }
                });
        }

        if (!$questionsOnly) {
            $this->info('Traduzindo respostas...');

            QuestionResponse::query()
                ->when(!$force, fn ($query) => $query->where(fn ($q) => $q->whereNull('response_pt')->orWhereNull('response_en')))
                ->chunkById(100, function ($responses) use ($force, &$translatedResponses, &$errors) {
                    foreach ($responses as $response) {
                        $result = $this->translateResponse($response, $force);

                        if ($result === true) {
                            $translatedResponses++;
                        } elseif ($result === false) {
                            $errors++;
                        }
                    }
                });
        }

        $this->newLine();
        $this->info("Perguntas traduzidas: {$translatedQuestions}");
        $this->info("Respostas traduzidas: {$translatedResponses}");

        if ($errors > 0) {
            $this->warn("Erros: {$errors} (use -v para detalhes)");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function translateQuestion(Question $question, bool $force): ?bool
    {
        if (!$force && filled($question->question_pt) && filled($question->question_en)) {
            return null;
        }

        if (blank($question->question)) {
            return null;
        }

        try {
            $translations = $this->translationService->getPortugueseAndEnglishTranslation($question->question);

            $question->update([
                'question_pt' => $translations['pt-br'],
                'question_en' => $translations['en'],
            ]);

            return true;
        } catch (ApiException $e) {
            $this->error("Erro ao traduzir pergunta {$question->id}: {$e->getMessage()}");
            return false;
        }
    }

    private function translateResponse(QuestionResponse $response, bool $force): ?bool
    {
        if (!$force && filled($response->response_pt) && filled($response->response_en)) {
            return null;
        }

        if (blank($response->response)) {
            return null;
        }

        try {
            $translations = $this->translationService->getPortugueseAndEnglishTranslation($response->response);

            $response->update([
                'response_pt' => $translations['pt-br'],
                'response_en' => $translations['en'],
            ]);

            return true;
        } catch (ApiException $e) {
            $this->error("Erro ao traduzir resposta {$response->id}: {$e->getMessage()}");
            return false;
        }
    }
}
