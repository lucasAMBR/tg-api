<?php

namespace App\Services\ScreeningQuestionnaire;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\DevScreeningQuestionnaireStatusEnum;
use App\Enums\ScreeningQuestionTypeEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Enums\TranslationStatusEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevScreeningQuestionnaire\DevScreeningQuestionnaireResource;
use App\Http\Resources\ScreeningQuestionnaire\ScreeningQuestionnaireResource;
use App\Jobs\TranslateContentJob;
use App\Models\DevJobVacancy;
use App\Models\DevScreeningAnswer;
use App\Models\DevScreeningQuestionnaire;
use App\Models\JobVacancy;
use App\Models\ScreeningQuestion;
use App\Models\ScreeningQuestionOption;
use App\Models\ScreeningQuestionnaire;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScreeningQuestionnaireService {

    /**
     * Relações que montam o questionário completo (perguntas e alternativas, na
     * ordem definida pela empresa)
     */
    private const QUESTIONNAIRE_RELATIONS = ['questions.options'];

    /**
     * Número mínimo de alternativas de uma pergunta de escolha
     */
    private const MIN_OPTIONS = 2;

    /**
     * Prazo padrão, em dias, para o dev responder o questionário quando a empresa não
     * informa uma data
     */
    public const DUE_DAYS = 7;

    /**
     * Empresa cria o questionário de triagem da vaga, com as perguntas e, nas de
     * escolha, as alternativas. Cada texto entra na fila de tradução
     */
    public function store(array $data): ScreeningQuestionnaireResource {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $this->ensureVacancyHasScreeningStep($jobVacancy);

        if($this->getVacancyQuestionnaire($jobVacancy)) {
            throw new ApiException("This vacancy already has a screening questionnaire!");
        }

        $questions = $this->validateQuestionsPayload($data['questions']);

        return DB::transaction(function() use ($jobVacancy, $data, $questions) {

            $questionnaire = ScreeningQuestionnaire::create([
                'job_vacancy_id' => $jobVacancy->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null
            ]);

            TranslateContentJob::dispatch($questionnaire);

            foreach($questions as $question) {
                $this->createQuestion($questionnaire, $question);
            }

            // A vaga pode ter avançado para a etapa antes do questionário existir. Nesse
            // caso as candidaturas estão paradas aguardando o formulário e começam a
            // responder agora
            $this->startAwaitingScreeningStep($jobVacancy, $this->resolveDueDate($data));

            return new ScreeningQuestionnaireResource(
                $questionnaire->load(self::QUESTIONNAIRE_RELATIONS)
            );

        });

    }

    /**
     * Questionário de triagem da vaga, do ponto de vista da empresa
     */
    public function showByJobVacancy(array $data): ScreeningQuestionnaireResource {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $questionnaire = $this->getVacancyQuestionnaire($jobVacancy);

        if(!$questionnaire) {
            throw new ApiException("This vacancy does not have a screening questionnaire yet!", 404);
        }

        return new ScreeningQuestionnaireResource(
            $questionnaire->loadCount('devQuestionnaires')->load(self::QUESTIONNAIRE_RELATIONS)
        );

    }

    /**
     * Empresa mantém o questionário: a lista de perguntas enviada substitui a atual,
     * atualizando as que vierem com `id`, criando as que vierem sem e removendo as
     * que ficarem de fora. O mesmo vale para as alternativas de cada pergunta.
     *
     * Só é aceito enquanto a etapa não começou, ou seja, enquanto nenhum candidato
     * recebeu o questionário para responder
     */
    public function update(array $data): ScreeningQuestionnaireResource {

        $questionnaire = $this->getCompanyQuestionnaire($data['id']);

        $this->ensureQuestionnaireNotInUse($questionnaire);

        $questions = $this->validateQuestionsPayload($data['questions']);

        // Perguntas informadas com `id` precisam ser desse questionário
        $currentQuestionIds = $questionnaire->questions()->pluck('id')->all();

        $informedIds = array_values(array_filter(array_column($questions, 'id')));

        $unknownIds = array_diff($informedIds, $currentQuestionIds);

        if($unknownIds !== []) {
            throw new ApiException(
                "Some of the informed questions do not belong to this questionnaire!",
                400,
                ['questions' => array_values($unknownIds)]
            );
        }

        return DB::transaction(function() use ($questionnaire, $data, $questions, $currentQuestionIds, $informedIds) {

            $this->updateQuestionnaireContent($questionnaire, $data);

            foreach($questions as $question) {

                if(isset($question['id'])) {
                    $this->updateQuestion($questionnaire->questions()->findOrFail($question['id']), $question);
                    continue;
                }

                $this->createQuestion($questionnaire, $question);

            }

            // As perguntas que ficaram de fora da lista enviada saem do questionário,
            // junto com as alternativas delas
            $removedIds = array_diff($currentQuestionIds, $informedIds);

            if($removedIds !== []) {
                ScreeningQuestionOption::whereIn('screening_question_id', $removedIds)->delete();
                ScreeningQuestion::whereIn('id', $removedIds)->delete();
            }

            return new ScreeningQuestionnaireResource(
                $questionnaire->fresh()->load(self::QUESTIONNAIRE_RELATIONS)
            );

        });

    }

    /**
     * Empresa remove o questionário da vaga, enquanto a etapa não começou
     */
    public function destroy(array $data): void {

        $questionnaire = $this->getCompanyQuestionnaire($data['id']);

        $this->ensureQuestionnaireNotInUse($questionnaire);

        DB::transaction(function() use ($questionnaire) {
            $questionnaire->questions()->each(function(ScreeningQuestion $question) {
                $question->options()->delete();
                $question->delete();
            });

            $questionnaire->delete();
        });

    }

    /**
     * Abre o preenchimento do questionário para cada candidatura que entrou na etapa
     * de perguntas de triagem e avisa o desenvolvedor do prazo de resposta. Sem
     * questionário cadastrado na vaga não há o que responder e nada é criado
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $approved
     */
    public function createDevQuestionnaires(JobVacancy $jobVacancy, Collection $approved, Carbon $dueDate): void {

        $questionnaire = $this->getVacancyQuestionnaire($jobVacancy);

        if(!$questionnaire) {
            return;
        }

        foreach($approved as $application) {

            // Um único preenchimento por candidatura, mesmo que a etapa seja reprocessada
            DevScreeningQuestionnaire::firstOrCreate(
                [
                    'screening_questionnaire_id' => $questionnaire->id,
                    'dev_job_vacancy_id' => $application->id
                ],
                [
                    'dev_profile_id' => $application->dev_profile_id,
                    'due_date' => $dueDate
                ]
            );

            $application->devProfile?->notifications()->create([
                'type' => 'screening_questionnaire_created',
                'title' => 'Questionário de triagem',
                'message' => "A empresa enviou o questionário de triagem da vaga {$jobVacancy->title}. Responda até {$dueDate->format('d/m/Y')} para seguir no processo seletivo!",
                'link' => env('APP_URL') . '/jobs'
            ]);

        }

    }

    /**
     * Coloca em andamento a etapa de perguntas de triagem de uma vaga que estava
     * aguardando o cadastro do questionário: as candidaturas paradas na espera passam
     * para a etapa em si e recebem o formulário para responder.
     *
     * Vaga que não está nessa espera não é afetada
     */
    private function startAwaitingScreeningStep(JobVacancy $jobVacancy, Carbon $dueDate): void {

        if($jobVacancy->process_step !== SelectionProcessStageEnum::AWAITING_SCREENING_QUESTIONS) {
            return;
        }

        $applications = $jobVacancy->applications()->with('devProfile')
            ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
            ->where('process_step', SelectionProcessStageEnum::AWAITING_SCREENING_QUESTIONS)
            ->get();

        if($applications->isNotEmpty()) {
            DevJobVacancy::whereIn('id', $applications->pluck('id'))
                ->update(['process_step' => SelectionProcessStageEnum::SCREENING_QUESTIONS->value]);
        }

        $jobVacancy->update(['process_step' => SelectionProcessStageEnum::SCREENING_QUESTIONS->value]);

        $this->createDevQuestionnaires($jobVacancy, $applications, $dueDate);

    }

    /**
     * Prazo de resposta informado pela empresa, ou o padrão quando ela não informa
     */
    private function resolveDueDate(array $data): Carbon {

        return isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : now()->addDays(self::DUE_DAYS);

    }

    /**
     * Questionário que o desenvolvedor autenticado tem para responder, com as
     * perguntas, as alternativas e, se já tiver respondido, as respostas enviadas
     */
    public function showDevQuestionnaire(array $data): DevScreeningQuestionnaireResource {

        $devQuestionnaire = $this->getDevQuestionnaire($data['id']);

        return new DevScreeningQuestionnaireResource(
            $devQuestionnaire->load([
                'questionnaire.questions.options',
                'answers.options',
                'answers.question'
            ])
        );

    }

    /**
     * Desenvolvedor responde o questionário de triagem. As perguntas obrigatórias
     * precisam vir respondidas e cada resposta precisa bater com o tipo da pergunta:
     * texto nas dissertativas, uma opção nas de escolha única e ao menos uma nas de
     * múltipla escolha
     */
    public function answer(array $data): DevScreeningQuestionnaireResource {

        $devQuestionnaire = $this->getDevQuestionnaire($data['id']);

        if($devQuestionnaire->isAnswered()) {
            throw new ApiException("This screening questionnaire was already answered!");
        }

        $application = $devQuestionnaire->devJobVacancy;

        if($application?->status !== DevJobVacancyStatusEnum::IN_PROGRESS) {
            throw new ApiException("This application is no longer in progress!");
        }

        if($application->process_step !== SelectionProcessStageEnum::SCREENING_QUESTIONS) {
            throw new ApiException("This application is not in the screening questions step!");
        }

        $questions = $devQuestionnaire->questionnaire?->questions()->with('options')->get()
            ?? collect();

        $answers = $this->validateAnswersPayload($questions, $data['answers']);

        return DB::transaction(function() use ($devQuestionnaire, $answers) {

            foreach($answers as $answer) {

                $newAnswer = DevScreeningAnswer::create([
                    'dev_screening_questionnaire_id' => $devQuestionnaire->id,
                    'screening_question_id' => $answer['question_id'],
                    'response' => $answer['response']
                ]);

                if($answer['option_ids'] !== []) {
                    $newAnswer->options()->attach($answer['option_ids']);
                }

            }

            $devQuestionnaire->update([
                'status' => DevScreeningQuestionnaireStatusEnum::ANSWERED,
                'submitted_at' => now()
            ]);

            $this->notifyCompany($devQuestionnaire);

            return new DevScreeningQuestionnaireResource(
                $devQuestionnaire->fresh()->load([
                    'questionnaire.questions.options',
                    'answers.options',
                    'answers.question'
                ])
            );

        });

    }

    /**
     * Cria a pergunta e, quando for de escolha, as alternativas. Cada texto vai para
     * a fila de tradução
     */
    private function createQuestion(ScreeningQuestionnaire $questionnaire, array $data): void {

        $question = ScreeningQuestion::create([
            'screening_questionnaire_id' => $questionnaire->id,
            'question' => $data['question'],
            'type' => $data['type'],
            'is_required' => $data['is_required'] ?? true,
            'order' => $data['order']
        ]);

        TranslateContentJob::dispatch($question);

        foreach($data['options'] as $option) {
            $this->createOption($question, $option);
        }

    }

    private function createOption(ScreeningQuestion $question, array $data): void {

        $option = ScreeningQuestionOption::create([
            'screening_question_id' => $question->id,
            'option' => $data['option'],
            'order' => $data['order']
        ]);

        TranslateContentJob::dispatch($option);

    }

    /**
     * Atualiza a pergunta e sincroniza as alternativas. O texto só volta para a fila
     * de tradução quando muda de fato, para não retraduzir o que já está traduzido
     */
    private function updateQuestion(ScreeningQuestion $question, array $data): void {

        $questionChanged = $question->question !== $data['question'];

        $question->update([
            'question' => $data['question'],
            'type' => $data['type'],
            'is_required' => $data['is_required'] ?? true,
            'order' => $data['order'],
            'translation_status' => $questionChanged
                ? TranslationStatusEnum::PENDING->value
                : $question->translation_status
        ]);

        if($questionChanged) {
            TranslateContentJob::dispatch($question);
        }

        $this->syncQuestionOptions($question, $data['options']);

    }

    /**
     * A lista de alternativas enviada substitui a atual da pergunta
     *
     * @param array<int, array<string, mixed>> $options
     */
    private function syncQuestionOptions(ScreeningQuestion $question, array $options): void {

        $currentIds = $question->options()->pluck('id')->all();

        $informedIds = array_values(array_filter(array_column($options, 'id')));

        $unknownIds = array_diff($informedIds, $currentIds);

        if($unknownIds !== []) {
            throw new ApiException(
                "Some of the informed options do not belong to their question!",
                400,
                ['options' => array_values($unknownIds)]
            );
        }

        foreach($options as $option) {

            if(!isset($option['id'])) {
                $this->createOption($question, $option);
                continue;
            }

            $currentOption = $question->options()->findOrFail($option['id']);

            $optionChanged = $currentOption->option !== $option['option'];

            $currentOption->update([
                'option' => $option['option'],
                'order' => $option['order'],
                'translation_status' => $optionChanged
                    ? TranslationStatusEnum::PENDING->value
                    : $currentOption->translation_status
            ]);

            if($optionChanged) {
                TranslateContentJob::dispatch($currentOption);
            }

        }

        $removedIds = array_diff($currentIds, $informedIds);

        if($removedIds !== []) {
            ScreeningQuestionOption::whereIn('id', $removedIds)->delete();
        }

    }

    /**
     * Atualiza título e descrição, mandando traduzir de novo só quando algum dos
     * dois muda
     */
    private function updateQuestionnaireContent(ScreeningQuestionnaire $questionnaire, array $data): void {

        $description = $data['description'] ?? null;

        $contentChanged = $questionnaire->title !== $data['title']
            || $questionnaire->description !== $description;

        $questionnaire->update([
            'title' => $data['title'],
            'description' => $description,
            'translation_status' => $contentChanged
                ? TranslationStatusEnum::PENDING->value
                : $questionnaire->translation_status
        ]);

        if($contentChanged) {
            TranslateContentJob::dispatch($questionnaire);
        }

    }

    /**
     * Regras de negócio das perguntas enviadas pela empresa: a dissertativa é
     * respondida com texto livre e não tem alternativas, e as de escolha precisam de
     * pelo menos duas
     *
     * @return array<int, array<string, mixed>> as perguntas com `options` sempre presente
     */
    private function validateQuestionsPayload(array $questions): array {

        $validated = [];

        foreach($questions as $index => $question) {

            $type = ScreeningQuestionTypeEnum::from($question['type']);

            $options = $question['options'] ?? [];

            if(!$type->hasOptions() && $options !== []) {
                throw new ApiException(
                    "An essay question can't have options!",
                    400,
                    ['questions' => [$index]]
                );
            }

            if($type->hasOptions() && count($options) < self::MIN_OPTIONS) {
                throw new ApiException(
                    "A choice question needs at least " . self::MIN_OPTIONS . " options!",
                    400,
                    ['questions' => [$index]]
                );
            }

            $question['options'] = $options;

            $validated[] = $question;

        }

        return $validated;

    }

    /**
     * Regras de negócio das respostas enviadas pelo dev: toda pergunta obrigatória
     * respondida, cada resposta no formato do tipo da pergunta e as opções marcadas
     * pertencendo à própria pergunta
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\ScreeningQuestion> $questions
     * @return array<int, array{question_id: string, response: string|null, option_ids: array<int, string>}>
     */
    private function validateAnswersPayload(Collection $questions, array $answers): array {

        $answersByQuestion = collect($answers)->keyBy('question_id');

        // Respostas de perguntas que não são desse questionário não são aceitas
        $unknownIds = $answersByQuestion->keys()->diff($questions->pluck('id'));

        if($unknownIds->isNotEmpty()) {
            throw new ApiException(
                "Some of the answered questions do not belong to this questionnaire!",
                400,
                ['answers' => $unknownIds->values()->all()]
            );
        }

        $validated = [];

        foreach($questions as $question) {

            $answer = $answersByQuestion->get($question->id);

            $response = isset($answer['response']) ? trim($answer['response']) : null;
            $optionIds = array_values(array_unique($answer['option_ids'] ?? []));

            $isEmpty = ($response === null || $response === '') && $optionIds === [];

            if($isEmpty) {

                if($question->is_required) {
                    throw new ApiException(
                        "All the required questions need to be answered!",
                        400,
                        ['questions' => [$question->id]]
                    );
                }

                continue;

            }

            $validated[] = $question->type->hasOptions()
                ? [
                    'question_id' => $question->id,
                    'response' => null,
                    'option_ids' => $this->validateChoiceAnswer($question, $optionIds)
                ]
                : [
                    'question_id' => $question->id,
                    'response' => $this->validateEssayAnswer($question, $response, $optionIds),
                    'option_ids' => []
                ];

        }

        return $validated;

    }

    /**
     * Opções marcadas em uma pergunta de escolha: precisam ser da própria pergunta e
     * respeitar o limite de uma só na escolha única
     *
     * @param array<int, string> $optionIds
     * @return array<int, string>
     */
    private function validateChoiceAnswer(ScreeningQuestion $question, array $optionIds): array {

        if($optionIds === []) {
            throw new ApiException(
                "A choice question needs to be answered with at least one option!",
                400,
                ['questions' => [$question->id]]
            );
        }

        if(!$question->type->allowsMultipleOptions() && count($optionIds) > 1) {
            throw new ApiException(
                "A single choice question accepts only one option!",
                400,
                ['questions' => [$question->id]]
            );
        }

        $unknownIds = array_diff($optionIds, $question->options->pluck('id')->all());

        if($unknownIds !== []) {
            throw new ApiException(
                "Some of the informed options do not belong to their question!",
                400,
                ['options' => array_values($unknownIds)]
            );
        }

        return $optionIds;

    }

    /**
     * Resposta de uma dissertativa: texto livre, sem opções marcadas
     *
     * @param array<int, string> $optionIds
     */
    private function validateEssayAnswer(ScreeningQuestion $question, ?string $response, array $optionIds): string {

        if($optionIds !== []) {
            throw new ApiException(
                "An essay question can't be answered with options!",
                400,
                ['questions' => [$question->id]]
            );
        }

        if($response === null || $response === '') {
            throw new ApiException(
                "An essay question needs to be answered with a text!",
                400,
                ['questions' => [$question->id]]
            );
        }

        return $response;

    }

    /**
     * Avisa a empresa de que o candidato respondeu o questionário de triagem
     */
    private function notifyCompany(DevScreeningQuestionnaire $devQuestionnaire): void {

        $jobVacancy = $devQuestionnaire->devJobVacancy?->jobVacancy;
        $devName = $devQuestionnaire->devProfile?->name;

        $jobVacancy?->companyProfile?->notifications()->create([
            'type' => 'screening_questionnaire_answered',
            'title' => 'Questionário de triagem respondido',
            'message' => "O desenvolvedor {$devName} respondeu o questionário de triagem da vaga {$jobVacancy->title}.",
            'link' => env('APP_URL') . "/job-vacancy/{$jobVacancy->id}"
        ]);

    }

    /**
     * Questionário vigente da vaga, ou null quando ela ainda não tem um. É o que diz
     * se a etapa de perguntas de triagem pode começar
     */
    public function getVacancyQuestionnaire(JobVacancy $jobVacancy): ?ScreeningQuestionnaire {

        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return ScreeningQuestionnaire::query()
            ->where('job_vacancy_id', $jobVacancy->id)
            ->latest('created_at')
            ->first();

    }

    /**
     * O questionário só pode ser alterado enquanto a etapa não começou, ou seja,
     * enquanto nenhum candidato o recebeu para responder
     */
    private function ensureQuestionnaireNotInUse(ScreeningQuestionnaire $questionnaire): void {

        if($questionnaire->devQuestionnaires()->exists()) {
            throw new ApiException("This questionnaire was already sent to the candidates and can't be changed anymore!");
        }

    }

    /**
     * O questionário só faz sentido em uma vaga que tem a etapa de perguntas de
     * triagem no processo seletivo
     */
    private function ensureVacancyHasScreeningStep(JobVacancy $jobVacancy): void {

        $hasStep = $jobVacancy->processSteps()
            ->where('step', SelectionProcessStageEnum::SCREENING_QUESTIONS->value)
            ->exists();

        if(!$hasStep) {
            throw new ApiException("This vacancy does not have the screening questions step in its selection process!");
        }

    }

    /**
     * Garante que a vaga pertence ao perfil de empresa autenticado
     */
    private function getCompanyJobVacancy(string $jobVacancyId): JobVacancy {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't manage the screening questionnaire of this vacancy!", 403);
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $jobVacancy = JobVacancy::query()
            ->where('id', $jobVacancyId)
            ->where('company_profile_id', $companyProfile->id)
            ->first();

        if(!$jobVacancy) {
            throw new ApiException("This vacancy does not belong to your company!", 403);
        }

        return $jobVacancy;

    }

    /**
     * Garante que o questionário é de uma vaga do perfil de empresa autenticado
     */
    private function getCompanyQuestionnaire(string $questionnaireId): ScreeningQuestionnaire {

        $questionnaire = ScreeningQuestionnaire::findOrFail($questionnaireId);

        $this->getCompanyJobVacancy($questionnaire->job_vacancy_id);

        return $questionnaire;

    }

    /**
     * Garante que o preenchimento é de uma candidatura do perfil de dev autenticado
     */
    private function getDevQuestionnaire(string $devQuestionnaireId): DevScreeningQuestionnaire {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't answer this screening questionnaire!", 403);
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $devQuestionnaire = DevScreeningQuestionnaire::query()
            ->with(['devJobVacancy.jobVacancy', 'devProfile', 'questionnaire'])
            ->where('id', $devQuestionnaireId)
            ->where('dev_profile_id', $devProfile->id)
            ->first();

        if(!$devQuestionnaire) {
            throw new ApiException("This screening questionnaire does not belong to you!", 403);
        }

        return $devQuestionnaire;

    }

}
