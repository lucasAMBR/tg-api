<?php

namespace App\Console\Commands;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Jobs\TranslateContentJob;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

/**
 * Traduz o que ficou com translation_status pending em qualquer model traduzível: registros de
 * antes de o model virar traduzível, ou que nunca chegaram a entrar na fila.
 *
 * Por padrão traduz aqui mesmo, um registro por vez, porque o Horizon tem um único worker para
 * todas as filas e uma carga grande na fila translations seguraria os outros jobs até acabar.
 */
class TranslatePendingContentCommand extends Command
{
    protected $signature = 'translations:translate-pending
                            {--model=* : Traduzir só estes models, pelo nome da classe (ex.: --model=Post --model=Comment)}
                            {--queue : Enviar para a fila translations em vez de traduzir aqui}
                            {--dry-run : Só mostrar quantos registros estão pendentes em cada model}';

    protected $description = 'Traduz para português e inglês tudo que está com translation_status pending, em todos os models traduzíveis';

    public function handle(TranslationService $translationService): int
    {
        $models = $this->translatableModels();
        $onlyModels = $this->option('model');

        $unknownModels = array_diff($onlyModels, $models->keys()->all());

        if ($unknownModels !== []) {
            $this->error('Model não traduzível: ' . implode(', ', $unknownModels) . '.');
            $this->line('Opções: ' . $models->keys()->implode(', '));
            return self::FAILURE;
        }

        if ($onlyModels !== []) {
            $models = $models->only($onlyModels);
        }

        $pendingCounts = $models
            ->map(fn (string $class) => $this->pendingQuery($class)->count())
            ->filter();

        if ($pendingCounts->isEmpty()) {
            $this->comment('Nada pendente de tradução.');
            return self::SUCCESS;
        }

        $this->table(['Model', 'Pendentes'], $pendingCounts->map(fn (int $count, string $name) => [$name, $count])->values());

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            foreach ($pendingCounts->keys() as $name) {
                $this->pendingQuery($models[$name])
                    ->lazyById(100)
                    ->each(fn (Model&Translatable $model) => TranslateContentJob::dispatch($model));
            }

            $this->info("{$pendingCounts->sum()} registros enviados para a fila translations.");
            return self::SUCCESS;
        }

        $results = [];

        foreach ($pendingCounts as $name => $count) {
            $this->info("Traduzindo {$name}...");

            $results[$name] = $this->translateModel($models[$name], $count, $translationService);

            $this->newLine();
        }

        $this->newLine();
        $this->table(['Model', 'Traduzidos', 'Erros'], collect($results)->map(fn (array $result, string $name) => [
            $name,
            $result['translated'],
            $result['errors'],
        ])->values());

        $errors = array_sum(array_column($results, 'errors'));

        if ($errors > 0) {
            $this->warn("Erros: {$errors}. Eles ficaram com translation_status error.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Roda o TranslateContentJob de cada registro aqui mesmo, para o resultado ser o mesmo da fila:
     * o status passa por translating e termina em translated, ou em error com o log da falha.
     *
     * @param  class-string<Model&Translatable>  $class
     * @return array{translated: int, errors: int}
     */
    private function translateModel(string $class, int $count, TranslationService $translationService): array
    {
        $result = ['translated' => 0, 'errors' => 0];

        $bar = $this->createTranslationProgressBar($count);
        $bar->start();

        // lazyById pagina pela chave, então os registros que saem de pending no caminho não fazem
        // a próxima página pular ninguém
        $this->pendingQuery($class)->lazyById(100)->each(function (Model&Translatable $model) use ($translationService, $bar, &$result) {
            $job = new TranslateContentJob($model);

            try {
                $job->handle($translationService);
                $result['translated']++;
            } catch (Throwable $e) {
                $job->failed($e);
                $result['errors']++;

                $bar->clear();
                $this->line('<fg=red>✗</> ' . class_basename($model) . " {$model->getKey()}: {$e->getMessage()}");
                $bar->display();
            }

            $bar->setMessage("<fg=green>OK: {$result['translated']}</> | <fg=red>Erros: {$result['errors']}</>");
            $bar->advance();
        });

        $bar->finish();

        return $result;
    }

    /**
     * Todos os models de app/Models que implementam Translatable, pelo nome da classe. Lidos da
     * pasta para que um model que vire traduzível entre aqui sem mexer no comando.
     *
     * @return Collection<string, class-string<Model&Translatable>>
     */
    private function translatableModels(): Collection
    {
        return collect(File::files(app_path('Models')))
            ->map(fn (SplFileInfo $file) => 'App\\Models\\' . $file->getFilenameWithoutExtension())
            ->filter(fn (string $class) => is_a($class, Translatable::class, true))
            ->mapWithKeys(fn (string $class) => [class_basename($class) => $class])
            ->sortKeys();
    }

    /**
     * @param  class-string<Model&Translatable>  $class
     */
    private function pendingQuery(string $class): Builder
    {
        return $class::query()->where('translation_status', TranslationStatusEnum::PENDING->value);
    }

    private function createTranslationProgressBar(int $total): ProgressBar
    {
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('<fg=green>OK: 0</> | <fg=red>Erros: 0</>');

        return $bar;
    }
}
