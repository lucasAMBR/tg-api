<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum SelectionProcessStageEnum: string
{
    use EnumHelper;

    case AWAITING_RESUME_SCREENING = 'awaiting_resume_screening';
    case RESUME_SCREENING = 'resume_screening';
    case AWAITING_SCREENING_QUESTIONS = 'awaiting_screening_questions';
    case SCREENING_QUESTIONS = 'screening_questions';
    case AWAITING_INTERVIEW = 'awaiting_interview';
    case INTERVIEW = 'interview';
    case AWAITING_TECHNICAL_CHALLENGE = 'awaiting_technical_challenge';
    case TECHNICAL_CHALLENGE = 'technical_challenge';
    case AWAITING_LIVE_CODING = 'awaiting_live_coding';
    case LIVE_CODING = 'live_coding';
    case AWAITING_LANGUAGE_ASSESSMENT = 'awaiting_language_assessment';
    case LANGUAGE_ASSESSMENT = 'language_assessment';
    case AWAITING_PORTFOLIO_REVIEW = 'awaiting_portfolio_review';
    case PORTFOLIO_REVIEW = 'portfolio_review';

    /**
     * Prefixo que liga cada etapa à espera pelo início dela
     */
    private const AWAITING_PREFIX = 'awaiting_';

    /**
     * Indica se a etapa é a espera pelo início da etapa em si, ou seja, uma das
     * `awaiting_*`. É onde as candidaturas ficam enquanto a empresa não prepara o
     * que a etapa exige (ex.: o questionário das perguntas de triagem)
     */
    public function isAwaiting(): bool
    {
        return str_starts_with($this->value, self::AWAITING_PREFIX);
    }

    /**
     * A etapa em si, sem a espera. Uma etapa que já começou devolve ela mesma
     */
    public function startedStage(): self
    {
        if(!$this->isAwaiting()) {
            return $this;
        }

        return self::tryFrom(substr($this->value, strlen(self::AWAITING_PREFIX))) ?? $this;
    }

    /**
     * A espera pelo início da etapa. Uma etapa que já está em espera devolve ela mesma
     */
    public function awaitingStage(): self
    {
        if($this->isAwaiting()) {
            return $this;
        }

        return self::tryFrom(self::AWAITING_PREFIX . $this->value) ?? $this;
    }

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_RESUME_SCREENING => 'Aguardando triagem de currículos',
            self::RESUME_SCREENING => 'Triagem de currículo',
            self::AWAITING_SCREENING_QUESTIONS => 'Aguardando perguntas de triagem',
            self::SCREENING_QUESTIONS => 'Perguntas de triagem',
            self::AWAITING_INTERVIEW => 'Aguardando entrevista',
            self::INTERVIEW => 'Entrevista',
            self::AWAITING_TECHNICAL_CHALLENGE => 'Aguardando desafio técnico',
            self::TECHNICAL_CHALLENGE => 'Desafio técnico',
            self::AWAITING_LIVE_CODING => 'Aguardando live coding',
            self::LIVE_CODING => 'Live coding',
            self::AWAITING_LANGUAGE_ASSESSMENT => 'Aguardando avaliação de idioma',
            self::LANGUAGE_ASSESSMENT => 'Avaliação de idioma',
            self::AWAITING_PORTFOLIO_REVIEW => 'Aguardando análise de portfólio',
            self::PORTFOLIO_REVIEW => 'Análise de portfólio'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::AWAITING_RESUME_SCREENING => 'enum.selection_process.stage.awaiting_resume_screening',
            self::RESUME_SCREENING => 'enum.selection_process.stage.resume_screening',
            self::AWAITING_SCREENING_QUESTIONS => 'enum.selection_process.stage.awaiting_screening_questions',
            self::SCREENING_QUESTIONS => 'enum.selection_process.stage.screening_questions',
            self::AWAITING_INTERVIEW => 'enum.selection_process.stage.awaiting_interview',
            self::INTERVIEW => 'enum.selection_process.stage.interview',
            self::AWAITING_TECHNICAL_CHALLENGE => 'enum.selection_process.stage.awaiting_technical_challenge',
            self::TECHNICAL_CHALLENGE => 'enum.selection_process.stage.technical_challenge',
            self::AWAITING_LIVE_CODING => 'enum.selection_process.stage.awaiting_live_coding',
            self::LIVE_CODING => 'enum.selection_process.stage.live_coding',
            self::AWAITING_LANGUAGE_ASSESSMENT => 'enum.selection_process.stage.awaiting_language_assessment',
            self::LANGUAGE_ASSESSMENT => 'enum.selection_process.stage.language_assessment',
            self::AWAITING_PORTFOLIO_REVIEW => 'enum.selection_process.stage.awaiting_portfolio_review',
            self::PORTFOLIO_REVIEW => 'enum.selection_process.stage.portfolio_review'
        };
    }
}
