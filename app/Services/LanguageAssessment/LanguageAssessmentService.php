<?php

namespace App\Services\LanguageAssessment;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\DevLanguageAssessmentStatusEnum;
use App\Enums\LanguageAssessmentQuestionTypeEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\DevLanguageAssessment\DevLanguageAssessmentResource;
use App\Http\Resources\LanguageAssessment\LanguageAssessmentResource;
use App\Models\DevJobVacancy;
use App\Models\DevLanguageAssessment;
use App\Models\DevLanguageAssessmentAnswer;
use App\Models\JobVacancy;
use App\Models\LanguageAssessment;
use App\Models\LanguageAssessmentQuestion;
use App\Models\LanguageAssessmentQuestionOption;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LanguageAssessmentService {

    /**
     * Relações que montam a prova completa (perguntas e alternativas, na ordem
     * definida pela empresa)
     */
    private const ASSESSMENT_RELATIONS = ['questions.options'];

    /**
     * Relações que montam a prova do dev: o formulário a responder e o que ele já
     * respondeu, com a correção
     */
    private const DEV_ASSESSMENT_RELATIONS = [
        'assessment.questions.options',
        'answers.question',
        'answers.options'
    ];

    /**
     * Número mínimo de alternativas de uma pergunta de escolha
     */
    private const MIN_OPTIONS = 2;

    /**
     * Empresa monta a prova de idioma da vaga, com as perguntas, as alternativas e o
     * gabarito de cada uma.
     *
     * NOTA: o conteúdo não entra na fila de tradução, de propósito. A prova é escrita
     * no idioma que está sendo avaliado — traduzi-la acabaria com o sentido dela
     */
    public function store(array $data): LanguageAssessmentResource {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $this->ensureVacancyHasLanguageAssessmentStep($jobVacancy);

        if($this->getVacancyAssessment($jobVacancy)) {
            throw new ApiException("This vacancy already has a language assessment!");
        }

        $questions = $this->validateQuestionsPayload($data['questions']);

        $this->validatePassingScore($data['passing_score'] ?? null, $questions);

        return DB::transaction(function() use ($jobVacancy, $data, $questions) {

            $assessment = LanguageAssessment::create([
                'job_vacancy_id' => $jobVacancy->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'language' => $data['language'],
                'passing_score' => $data['passing_score'] ?? null,
                'due_date' => Carbon::parse($data['due_date'])
            ]);

            foreach($questions as $question) {
                $this->createQuestion($assessment, $question);
            }

            // A vaga pode ter avançado para a etapa antes da prova existir. Nesse caso
            // as candidaturas estão paradas aguardando o formulário e começam a
            // responder agora
            $this->startAwaitingLanguageAssessmentStep($jobVacancy, $assessment);

            return new LanguageAssessmentResource(
                $assessment->load(self::ASSESSMENT_RELATIONS),
                withAnswerKey: true
            );

        });

    }

    /**
     * Prova de idioma da vaga, do ponto de vista da empresa — com o gabarito
     */
    public function showByJobVacancy(array $data): LanguageAssessmentResource {

        $jobVacancy = $this->getCompanyJobVacancy($data['job_vacancy_id']);

        $assessment = $this->getVacancyAssessment($jobVacancy);

        if(!$assessment) {
            throw new ApiException("This vacancy does not have a language assessment yet!", 404);
        }

        return new LanguageAssessmentResource(
            $assessment->loadCount('devAssessments')->load(self::ASSESSMENT_RELATIONS),
            withAnswerKey: true
        );

    }

    /**
     * Empresa mantém a prova: a lista de perguntas enviada substitui a atual,
     * atualizando as que vierem com `id`, criando as que vierem sem e removendo as
     * que ficarem de fora. O mesmo vale para as alternativas de cada pergunta.
     *
     * Só é aceito enquanto a etapa não começou, ou seja, enquanto nenhum candidato
     * recebeu a prova para responder
     */
    public function update(array $data): LanguageAssessmentResource {

        $assessment = $this->getCompanyAssessment($data['id']);

        $this->ensureAssessmentNotInUse($assessment);

        $questions = $this->validateQuestionsPayload($data['questions']);

        $this->validatePassingScore($data['passing_score'] ?? null, $questions);

        // Perguntas informadas com `id` precisam ser dessa prova
        $currentQuestionIds = $assessment->questions()->pluck('id')->all();

        $informedIds = array_values(array_filter(array_column($questions, 'id')));

        $unknownIds = array_diff($informedIds, $currentQuestionIds);

        if($unknownIds !== []) {
            throw new ApiException(
                "Some of the informed questions do not belong to this language assessment!",
                400,
                ['questions' => array_values($unknownIds)]
            );
        }

        return DB::transaction(function() use ($assessment, $data, $questions, $currentQuestionIds, $informedIds) {

            $assessment->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'language' => $data['language'],
                'passing_score' => $data['passing_score'] ?? null,
                'due_date' => Carbon::parse($data['due_date'])
            ]);

            foreach($questions as $question) {

                if(isset($question['id'])) {
                    $this->updateQuestion($assessment->questions()->findOrFail($question['id']), $question);
                    continue;
                }

                $this->createQuestion($assessment, $question);

            }

            // As perguntas que ficaram de fora da lista enviada saem da prova, junto
            // com as alternativas delas
            $removedIds = array_diff($currentQuestionIds, $informedIds);

            if($removedIds !== []) {
                LanguageAssessmentQuestionOption::whereIn('language_assessment_question_id', $removedIds)->delete();
                LanguageAssessmentQuestion::whereIn('id', $removedIds)->delete();
            }

            return new LanguageAssessmentResource(
                $assessment->fresh()->load(self::ASSESSMENT_RELATIONS),
                withAnswerKey: true
            );

        });

    }

    /**
     * Empresa remove a prova da vaga, enquanto a etapa não começou
     */
    public function destroy(array $data): void {

        $assessment = $this->getCompanyAssessment($data['id']);

        $this->ensureAssessmentNotInUse($assessment);

        DB::transaction(function() use ($assessment) {
            $assessment->questions()->each(function(LanguageAssessmentQuestion $question) {
                $question->options()->delete();
                $question->delete();
            });

            $assessment->delete();
        });

    }

    /**
     * Abre a prova para cada candidatura que entrou na etapa de avaliação de idioma e
     * avisa o desenvolvedor do prazo. Sem prova cadastrada na vaga não há o que
     * responder e nada é criado
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\DevJobVacancy> $approved
     */
    public function createDevAssessments(JobVacancy $jobVacancy, Collection $approved, ?Carbon $dueDate = null): void {

        $assessment = $this->getVacancyAssessment($jobVacancy);

        if(!$assessment) {
            return;
        }

        // O prazo é o da prova, definido pela empresa ao montar o formulário
        $deadline = $assessment->due_date ?? $dueDate;

        foreach($approved as $application) {

            // Uma única prova por candidatura, mesmo que a etapa seja reprocessada
            DevLanguageAssessment::firstOrCreate(
                [
                    'language_assessment_id' => $assessment->id,
                    'dev_job_vacancy_id' => $application->id
                ],
                [
                    'dev_profile_id' => $application->dev_profile_id,
                    'due_date' => $deadline
                ]
            );

            $application->devProfile?->notifications()->create([
                'type' => 'language_assessment_created',
                'title' => 'Prova de idioma',
                'message' => "A empresa enviou a prova de {$assessment->language} da vaga {$jobVacancy->title}. Responda até {$deadline->format('d/m/Y')} para seguir no processo seletivo!",
                'link' => env('APP_URL') . '/jobs'
            ]);

        }

    }

    /**
     * Prova que o desenvolvedor autenticado tem para responder, com as perguntas e as
     * alternativas — sem o gabarito enquanto ele não entregar. Depois da entrega vem
     * tudo: respostas certas, correção e nota
     */
    public function showDevAssessment(array $data): DevLanguageAssessmentResource {

        $devAssessment = $this->getDevAssessment($data['id']);

        return new DevLanguageAssessmentResource(
            $devAssessment->load(self::DEV_ASSESSMENT_RELATIONS)
        );

    }

    /**
     * Desenvolvedor entrega a prova. As respostas são conferidas contra o gabarito na
     * hora: cada pergunta certa soma o próprio peso, a nota final é comparada com a
     * nota de corte e o resultado fica gravado na prova.
     *
     * Perguntas não respondidas simplesmente valem zero — é prova, não formulário
     */
    public function answer(array $data): DevLanguageAssessmentResource {

        $devAssessment = $this->getDevAssessment($data['id']);

        if($devAssessment->isAnswered()) {
            throw new ApiException("This language assessment was already answered!");
        }

        $application = $devAssessment->devJobVacancy;

        if($application?->status !== DevJobVacancyStatusEnum::IN_PROGRESS) {
            throw new ApiException("This application is no longer in progress!");
        }

        if($application->process_step !== SelectionProcessStageEnum::LANGUAGE_ASSESSMENT) {
            throw new ApiException("This application is not in the language assessment step!");
        }

        $assessment = $devAssessment->assessment;

        $questions = $assessment?->questions()->with('options')->get() ?? collect();

        $answers = $this->validateAnswersPayload($questions, $data['answers']);

        return DB::transaction(function() use ($devAssessment, $assessment, $questions, $answers) {

            $score = 0;

            foreach($answers as $answer) {

                $question = $questions->firstWhere('id', $answer['question_id']);

                // A correção é feita aqui, na entrega: o gabarito nunca sai do backend
                // antes disso
                $isCorrect = $this->answerIsCorrect($question, $answer);
                $earnedScore = $isCorrect ? $question->weight : 0;

                $score += $earnedScore;

                $newAnswer = DevLanguageAssessmentAnswer::create([
                    'dev_language_assessment_id' => $devAssessment->id,
                    'language_assessment_question_id' => $question->id,
                    'response' => $answer['response'],
                    'is_correct' => $isCorrect,
                    'earned_score' => $earnedScore
                ]);

                if($answer['option_ids'] !== []) {
                    $newAnswer->options()->attach($answer['option_ids']);
                }

            }

            // A nota máxima conta todas as perguntas da prova, inclusive as que o dev
            // deixou em branco
            $maxScore = (int) $questions->sum('weight');

            $devAssessment->update([
                'status' => DevLanguageAssessmentStatusEnum::ANSWERED,
                'score' => $score,
                'max_score' => $maxScore,
                // Sem nota de corte a prova só pontua, sem veredito
                'passed' => $assessment?->passing_score === null
                    ? null
                    : $score >= $assessment->passing_score,
                'submitted_at' => now()
            ]);

            $this->notifyCompany($devAssessment->fresh());

            return new DevLanguageAssessmentResource(
                $devAssessment->fresh()->load(self::DEV_ASSESSMENT_RELATIONS)
            );

        });

    }

    /**
     * Prova vigente da vaga, ou null quando ela ainda não tem uma. É o que diz se a
     * etapa de avaliação de idioma pode começar
     */
    public function getVacancyAssessment(JobVacancy $jobVacancy): ?LanguageAssessment {

        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return LanguageAssessment::query()
            ->where('job_vacancy_id', $jobVacancy->id)
            ->latest('created_at')
            ->first();

    }

    /**
     * Confere uma resposta contra o gabarito da pergunta.
     *
     * Nas de escolha o conjunto marcado precisa bater exatamente com o conjunto certo
     * — na múltipla escolha não há acerto parcial. Nas escritas o texto é comparado
     * com a lista de respostas aceitas
     */
    private function answerIsCorrect(LanguageAssessmentQuestion $question, array $answer): bool {

        if($question->type->expectsText()) {

            $response = $this->normalizeText($answer['response'] ?? '');

            if($response === '') {
                return false;
            }

            foreach($question->expected_answers ?? [] as $expected) {
                if($this->normalizeText($expected) === $response) {
                    return true;
                }
            }

            return false;

        }

        $marked = $answer['option_ids'];
        $correct = $question->correctOptionIds();

        if($marked === [] || $correct === []) {
            return false;
        }

        sort($marked);
        sort($correct);

        return $marked === $correct;

    }

    /**
     * Normaliza o texto para a comparação da resposta escrita: ignora caixa e espaço
     * repetido, mas mantém o acento — em prova de idioma o acento faz parte da
     * resposta certa
     */
    private function normalizeText(string $value): string {

        return preg_replace('/\s+/u', ' ', trim(mb_strtolower($value, 'UTF-8')));

    }

    /**
     * Valida a lista de perguntas enviada pela empresa: o tipo dita se a pergunta tem
     * alternativas ou respostas aceitas, e toda pergunta precisa de gabarito
     *
     * @return array<int, array<string, mixed>>
     */
    private function validateQuestionsPayload(array $questions): array {

        $validated = [];

        foreach($questions as $index => $question) {

            $type = LanguageAssessmentQuestionTypeEnum::from($question['type']);

            $options = $question['options'] ?? [];
            $expectedAnswers = array_values(array_filter(
                $question['expected_answers'] ?? [],
                fn($expected) => trim((string) $expected) !== ''
            ));

            if($type->expectsText()) {

                if($options !== []) {
                    throw new ApiException(
                        "A text question can't have options!",
                        400,
                        ['questions' => [$index]]
                    );
                }

                if($expectedAnswers === []) {
                    throw new ApiException(
                        "A text question needs at least one accepted answer!",
                        400,
                        ['questions' => [$index]]
                    );
                }

            } else {

                if($expectedAnswers !== []) {
                    throw new ApiException(
                        "A choice question can't have accepted answers!",
                        400,
                        ['questions' => [$index]]
                    );
                }

                if(count($options) < self::MIN_OPTIONS) {
                    throw new ApiException(
                        "A choice question needs at least " . self::MIN_OPTIONS . " options!",
                        400,
                        ['questions' => [$index]]
                    );
                }

                $this->validateOptionsAnswerKey($type, $options, $index);

            }

            $question['options'] = $options;
            $question['expected_answers'] = $expectedAnswers;

            $validated[] = $question;

        }

        return $validated;

    }

    /**
     * Toda pergunta de escolha precisa de gabarito, e a de escolha única aceita uma
     * alternativa certa só — senão não haveria resposta possível
     *
     * @param array<int, array<string, mixed>> $options
     */
    private function validateOptionsAnswerKey(LanguageAssessmentQuestionTypeEnum $type, array $options, int $index): void {

        $correctCount = count(array_filter(
            $options,
            fn($option) => (bool) ($option['is_correct'] ?? false)
        ));

        if($correctCount === 0) {
            throw new ApiException(
                "A choice question needs at least one correct option!",
                400,
                ['questions' => [$index]]
            );
        }

        if(!$type->allowsMultipleOptions() && $correctCount > 1) {
            throw new ApiException(
                "A single choice question accepts only one correct option!",
                400,
                ['questions' => [$index]]
            );
        }

        if($correctCount === count($options)) {
            throw new ApiException(
                "A choice question can't have every option marked as correct!",
                400,
                ['questions' => [$index]]
            );
        }

    }

    /**
     * A nota de corte precisa caber na prova: acima da soma dos pesos ninguém
     * conseguiria passar
     *
     * @param array<int, array<string, mixed>> $questions
     */
    private function validatePassingScore(?int $passingScore, array $questions): void {

        if($passingScore === null) {
            return;
        }

        $maxScore = array_sum(array_map(
            fn($question) => (int) ($question['weight'] ?? 1),
            $questions
        ));

        if($passingScore > $maxScore) {
            throw new ApiException(
                "The passing score can't be higher than the maximum score of the assessment!",
                400,
                ['passing_score' => $passingScore, 'max_score' => $maxScore]
            );
        }

    }

    /**
     * Valida as respostas enviadas pelo dev: cada uma precisa ser de uma pergunta da
     * prova e bater com o tipo dela. Perguntas em branco são aceitas — valem zero
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\LanguageAssessmentQuestion> $questions
     * @return array<int, array<string, mixed>>
     */
    private function validateAnswersPayload(Collection $questions, array $answers): array {

        $answersByQuestion = collect($answers)->keyBy('question_id');

        // Respostas de perguntas que não são dessa prova não são aceitas
        $unknownIds = $answersByQuestion->keys()->diff($questions->pluck('id'));

        if($unknownIds->isNotEmpty()) {
            throw new ApiException(
                "Some of the answered questions do not belong to this language assessment!",
                400,
                ['answers' => $unknownIds->values()->all()]
            );
        }

        $validated = [];

        foreach($questions as $question) {

            $answer = $answersByQuestion->get($question->id);

            if($answer === null) {
                continue;
            }

            $response = isset($answer['response']) ? trim($answer['response']) : null;
            $optionIds = array_values(array_unique($answer['option_ids'] ?? []));

            // Em branco não é erro: a pergunta simplesmente não pontua
            if(($response === null || $response === '') && $optionIds === []) {
                continue;
            }

            $validated[] = $question->type->expectsText()
                ? [
                    'question_id' => $question->id,
                    'response' => $this->validateTextAnswer($question, $response, $optionIds),
                    'option_ids' => []
                ]
                : [
                    'question_id' => $question->id,
                    'response' => null,
                    'option_ids' => $this->validateChoiceAnswer($question, $optionIds)
                ];

        }

        return $validated;

    }

    /**
     * Alternativas marcadas em uma pergunta de escolha: precisam ser da própria
     * pergunta e respeitar o limite de uma só na escolha única
     *
     * @param array<int, string> $optionIds
     * @return array<int, string>
     */
    private function validateChoiceAnswer(LanguageAssessmentQuestion $question, array $optionIds): array {

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
     * Resposta de uma pergunta escrita: texto livre, sem alternativas marcadas
     *
     * @param array<int, string> $optionIds
     */
    private function validateTextAnswer(LanguageAssessmentQuestion $question, ?string $response, array $optionIds): string {

        if($optionIds !== []) {
            throw new ApiException(
                "A text question can't be answered with options!",
                400,
                ['questions' => [$question->id]]
            );
        }

        return (string) $response;

    }

    /**
     * Cria a pergunta e, quando for de escolha, as alternativas com o gabarito
     */
    private function createQuestion(LanguageAssessment $assessment, array $data): void {

        $question = LanguageAssessmentQuestion::create([
            'language_assessment_id' => $assessment->id,
            'question' => $data['question'],
            'type' => $data['type'],
            'expected_answers' => $data['expected_answers'] !== [] ? $data['expected_answers'] : null,
            'weight' => $data['weight'] ?? 1,
            'order' => $data['order']
        ]);

        foreach($data['options'] as $option) {
            $this->createOption($question, $option);
        }

    }

    private function createOption(LanguageAssessmentQuestion $question, array $data): void {

        LanguageAssessmentQuestionOption::create([
            'language_assessment_question_id' => $question->id,
            'option' => $data['option'],
            'is_correct' => $data['is_correct'] ?? false,
            'order' => $data['order']
        ]);

    }

    /**
     * Atualiza a pergunta e sincroniza as alternativas
     */
    private function updateQuestion(LanguageAssessmentQuestion $question, array $data): void {

        $question->update([
            'question' => $data['question'],
            'type' => $data['type'],
            'expected_answers' => $data['expected_answers'] !== [] ? $data['expected_answers'] : null,
            'weight' => $data['weight'] ?? 1,
            'order' => $data['order']
        ]);

        $this->syncQuestionOptions($question, $data['options']);

    }

    /**
     * A lista de alternativas enviada substitui a atual da pergunta
     *
     * @param array<int, array<string, mixed>> $options
     */
    private function syncQuestionOptions(LanguageAssessmentQuestion $question, array $options): void {

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

            if(isset($option['id'])) {
                $question->options()->findOrFail($option['id'])->update([
                    'option' => $option['option'],
                    'is_correct' => $option['is_correct'] ?? false,
                    'order' => $option['order']
                ]);
                continue;
            }

            $this->createOption($question, $option);

        }

        $removedIds = array_diff($currentIds, $informedIds);

        if($removedIds !== []) {
            LanguageAssessmentQuestionOption::whereIn('id', $removedIds)->delete();
        }

    }

    /**
     * Coloca em andamento a etapa de avaliação de idioma de uma vaga que estava
     * aguardando o cadastro da prova: as candidaturas paradas na espera passam para a
     * etapa em si e recebem o formulário para responder.
     *
     * Vaga que não está nessa espera não é afetada
     */
    private function startAwaitingLanguageAssessmentStep(JobVacancy $jobVacancy, LanguageAssessment $assessment): void {

        if($jobVacancy->process_step !== SelectionProcessStageEnum::AWAITING_LANGUAGE_ASSESSMENT) {
            return;
        }

        $applications = $jobVacancy->applications()->with('devProfile')
            ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS)
            ->where('process_step', SelectionProcessStageEnum::AWAITING_LANGUAGE_ASSESSMENT)
            ->get();

        if($applications->isNotEmpty()) {
            DevJobVacancy::whereIn('id', $applications->pluck('id'))
                ->update(['process_step' => SelectionProcessStageEnum::LANGUAGE_ASSESSMENT->value]);
        }

        $jobVacancy->update(['process_step' => SelectionProcessStageEnum::LANGUAGE_ASSESSMENT->value]);

        $this->createDevAssessments($jobVacancy, $applications, $assessment->due_date);

    }

    /**
     * Avisa a empresa de que o candidato entregou a prova, já com o resultado da
     * correção automática
     */
    private function notifyCompany(DevLanguageAssessment $devAssessment): void {

        $jobVacancy = $devAssessment->devJobVacancy?->jobVacancy;
        $devName = $devAssessment->devProfile?->name;

        $jobVacancy?->companyProfile?->notifications()->create([
            'type' => 'language_assessment_answered',
            'title' => 'Prova de idioma respondida',
            'message' => "O desenvolvedor {$devName} entregou a prova de idioma da vaga {$jobVacancy->title} e fez {$devAssessment->score} de {$devAssessment->max_score} pontos.",
            'link' => env('APP_URL') . "/job-vacancy/{$jobVacancy->id}"
        ]);

    }

    /**
     * A prova só pode ser alterada enquanto a etapa não começou, ou seja, enquanto
     * nenhum candidato a recebeu para responder
     */
    private function ensureAssessmentNotInUse(LanguageAssessment $assessment): void {

        if($assessment->devAssessments()->exists()) {
            throw new ApiException("This language assessment was already sent to the candidates and can't be changed anymore!");
        }

    }

    /**
     * A prova só faz sentido em uma vaga que tem a etapa de avaliação de idioma no
     * processo seletivo
     */
    private function ensureVacancyHasLanguageAssessmentStep(JobVacancy $jobVacancy): void {

        $hasStep = $jobVacancy->processSteps()
            ->where('step', SelectionProcessStageEnum::LANGUAGE_ASSESSMENT->value)
            ->exists();

        if(!$hasStep) {
            throw new ApiException("This vacancy does not have the language assessment step in its selection process!");
        }

    }

    /**
     * Garante que a vaga pertence ao perfil de empresa autenticado
     */
    private function getCompanyJobVacancy(string $jobVacancyId): JobVacancy {

        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You can't manage the language assessment of this vacancy!", 403);
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
     * Garante que a prova é de uma vaga do perfil de empresa autenticado
     */
    private function getCompanyAssessment(string $assessmentId): LanguageAssessment {

        $assessment = LanguageAssessment::findOrFail($assessmentId);

        $this->getCompanyJobVacancy($assessment->job_vacancy_id);

        return $assessment;

    }

    /**
     * Garante que a prova é do desenvolvedor autenticado
     */
    private function getDevAssessment(string $devAssessmentId): DevLanguageAssessment {

        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You can't answer this language assessment!", 403);
        }

        $devProfile = ProfileHelper::getUserProfileByRole($authUser);

        $devAssessment = DevLanguageAssessment::query()
            ->with(['devJobVacancy.jobVacancy', 'devProfile', 'assessment'])
            ->where('id', $devAssessmentId)
            ->where('dev_profile_id', $devProfile->id)
            ->first();

        if(!$devAssessment) {
            throw new ApiException("This language assessment does not belong to you!", 403);
        }

        return $devAssessment;

    }

}
