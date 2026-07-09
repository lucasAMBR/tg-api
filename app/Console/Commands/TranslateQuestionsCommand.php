<?php

namespace App\Console\Commands;

use App\Enums\TranslationStatusEnum;
use App\Exceptions\ApiException;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

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

            $questionsQuery = Question::query()
                ->when(!$force, fn ($query) => $query->where(fn ($q) => $q->whereNull('question_pt')->orWhereNull('question_en')));

            $totalQuestions = $questionsQuery->count();

            if ($totalQuestions === 0) {
                $this->comment('Nenhuma pergunta pendente.');
            } else {
                $bar = $this->createTranslationProgressBar($totalQuestions);
                $bar->start();

                $questionsQuery->chunkById(50, function ($questions) use ($force, $bar, &$translatedQuestions, &$errors) {
                    foreach ($questions as $question) {
                        $result = $this->translateQuestion($question, $force, $bar);

                        if ($result === true) {
                            $translatedQuestions++;
                        } elseif ($result === false) {
                            $errors++;
                        }

                        $this->advanceTranslationProgressBar($bar, $translatedQuestions, $errors);
                    }
                });

                $bar->finish();
                $this->newLine();
            }
        }

        if (!$questionsOnly) {
            $this->info('Traduzindo respostas...');

            $responsesQuery = QuestionResponse::query()
                ->when(!$force, fn ($query) => $query->where(fn ($q) => $q->whereNull('response_pt')->orWhereNull('response_en')));

            $totalResponses = $responsesQuery->count();

            if ($totalResponses === 0) {
                $this->comment('Nenhuma resposta pendente.');
            } else {
                $bar = $this->createTranslationProgressBar($totalResponses);
                $bar->start();

                $responsesQuery->chunkById(100, function ($responses) use ($force, $bar, &$translatedResponses, &$errors) {
                    foreach ($responses as $response) {
                        $result = $this->translateResponse($response, $force, $bar);

                        if ($result === true) {
                            $translatedResponses++;
                        } elseif ($result === false) {
                            $errors++;
                        }

                        $this->advanceTranslationProgressBar($bar, $translatedResponses, $errors);
                    }
                });

                $bar->finish();
                $this->newLine();
            }
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

    private function translateQuestion(Question $question, bool $force, ProgressBar $bar): ?bool
    {
        if (!$force && filled($question->question_pt) && filled($question->question_en)) {
            return null;
        }

        if (blank($question->question)) {
            return null;
        }

        $question->updateTranslationStatus(TranslationStatusEnum::TRANSLATING->value);

        try {
            $translations = $this->translationService->getPortugueseAndEnglishTranslation($question->question);

            $question->update([
                'question_pt' => $translations['pt-br'],
                'question_en' => $translations['en'],
                'translation_status' => TranslationStatusEnum::TRANSLATED->value,
            ]);

            $this->writeTranslationOutput($bar, "<fg=green>✓</> Pergunta {$question->id} traduzida");

            return true;
        } catch (ApiException $e) {
            $question->updateTranslationStatus(TranslationStatusEnum::ERROR->value);
            $this->writeTranslationOutput($bar, "<fg=red>✗</> Erro ao traduzir pergunta {$question->id}: {$e->getMessage()}");
            return false;
        }
    }

    private function translateResponse(QuestionResponse $response, bool $force, ProgressBar $bar): ?bool
    {
        if (!$force && filled($response->response_pt) && filled($response->response_en)) {
            return null;
        }

        if (blank($response->response)) {
            return null;
        }

        $response->updateTranslationStatus(TranslationStatusEnum::TRANSLATING->value);

        try {
            $translations = $this->translationService->getPortugueseAndEnglishTranslation($response->response);

            $response->update([
                'response_pt' => $translations['pt-br'],
                'response_en' => $translations['en'],
                'translation_status' => TranslationStatusEnum::TRANSLATED->value,
            ]);

            $this->writeTranslationOutput($bar, "<fg=green>✓</> Resposta {$response->id} traduzida");

            return true;
        } catch (ApiException $e) {
            $response->updateTranslationStatus(TranslationStatusEnum::ERROR->value);
            $this->writeTranslationOutput($bar, "<fg=red>✗</> Erro ao traduzir resposta {$response->id}: {$e->getMessage()}");
            return false;
        }
    }

    private function createTranslationProgressBar(int $total): ProgressBar
    {
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('<fg=green>OK: 0</> | <fg=red>Erros: 0</>');

        return $bar;
    }

    private function advanceTranslationProgressBar(ProgressBar $bar, int $translated, int $errors): void
    {
        $bar->setMessage("<fg=green>OK: {$translated}</> | <fg=red>Erros: {$errors}</>");
        $bar->advance();
    }

    private function writeTranslationOutput(ProgressBar $bar, string $message): void
    {
        $bar->clear();
        $this->line($message);
        $bar->display();
    }
}
