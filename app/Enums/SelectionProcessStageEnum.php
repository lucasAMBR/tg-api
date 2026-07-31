<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum SelectionProcessStageEnum: string
{
    use EnumHelper;

    case RESUME_SCREENING = 'resume_screening';
    case AWAITING_SCREENING_QUESTIONS = 'awaiting_screening_questions';
    case SCREENING_QUESTIONS = 'screening_questions';
    case AWAITING_HR_INTERVIEW = 'awaiting_hr_interview';
    case HR_INTERVIEW = 'hr_interview';
    case AWAITING_TECHNICAL_CHALLENGE = 'awaiting_technical_challenge';
    case TECHNICAL_CHALLENGE = 'technical_challenge';
    case AWAITING_LIVE_CODING = 'awaiting_live_coding';
    case LIVE_CODING = 'live_coding';
    case AWAITING_TECHNICAL_INTERVIEW = 'awaiting_technical_interview';
    case TECHNICAL_INTERVIEW = 'technical_interview';
    case AWAITING_BEHAVIORAL_INTERVIEW = 'awaiting_behavioral_interview';
    case BEHAVIORAL_INTERVIEW = 'behavioral_interview';
    case AWAITING_CULTURAL_FIT_INTERVIEW = 'awaiting_cultural_fit_interview';
    case CULTURAL_FIT_INTERVIEW = 'cultural_fit_interview';
    case AWAITING_GROUP_DYNAMIC = 'awaiting_group_dynamic';
    case GROUP_DYNAMIC = 'group_dynamic';
    case AWAITING_LANGUAGE_ASSESSMENT = 'awaiting_language_assessment';
    case LANGUAGE_ASSESSMENT = 'language_assessment';
    case AWAITING_PORTFOLIO_REVIEW = 'awaiting_portfolio_review';
    case PORTFOLIO_REVIEW = 'portfolio_review';
    case AWAITING_TEAM_INTERVIEW = 'awaiting_team_interview';
    case TEAM_INTERVIEW = 'team_interview';
    case AWAITING_FINAL_INTERVIEW = 'awaiting_final_interview';
    case FINAL_INTERVIEW = 'final_interview';
    case HIRED = 'hired';

    public function label(): string
    {
        return match ($this) {
            self::RESUME_SCREENING => 'Triagem de currículo',
            self::AWAITING_SCREENING_QUESTIONS => 'Aguardando perguntas de triagem',
            self::SCREENING_QUESTIONS => 'Perguntas de triagem',
            self::AWAITING_HR_INTERVIEW => 'Aguardando entrevista com RH',
            self::HR_INTERVIEW => 'Entrevista com RH',
            self::AWAITING_TECHNICAL_CHALLENGE => 'Aguardando desafio técnico',
            self::TECHNICAL_CHALLENGE => 'Desafio técnico',
            self::AWAITING_LIVE_CODING => 'Aguardando live coding',
            self::LIVE_CODING => 'Live coding',
            self::AWAITING_TECHNICAL_INTERVIEW => 'Aguardando entrevista técnica',
            self::TECHNICAL_INTERVIEW => 'Entrevista técnica',
            self::AWAITING_BEHAVIORAL_INTERVIEW => 'Aguardando entrevista comportamental',
            self::BEHAVIORAL_INTERVIEW => 'Entrevista comportamental',
            self::AWAITING_CULTURAL_FIT_INTERVIEW => 'Aguardando entrevista de fit cultural',
            self::CULTURAL_FIT_INTERVIEW => 'Entrevista de fit cultural',
            self::AWAITING_GROUP_DYNAMIC => 'Aguardando dinâmica em grupo',
            self::GROUP_DYNAMIC => 'Dinâmica em grupo',
            self::AWAITING_LANGUAGE_ASSESSMENT => 'Aguardando avaliação de idioma',
            self::LANGUAGE_ASSESSMENT => 'Avaliação de idioma',
            self::AWAITING_PORTFOLIO_REVIEW => 'Aguardando análise de portfólio',
            self::PORTFOLIO_REVIEW => 'Análise de portfólio',
            self::AWAITING_TEAM_INTERVIEW => 'Aguardando entrevista com o time',
            self::TEAM_INTERVIEW => 'Entrevista com o time',
            self::AWAITING_FINAL_INTERVIEW => 'Aguardando entrevista final',
            self::FINAL_INTERVIEW => 'Entrevista final',
            self::HIRED => 'Contratado'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::RESUME_SCREENING => 'enum.selection_process.stage.resume_screening',
            self::AWAITING_SCREENING_QUESTIONS => 'enum.selection_process.stage.awaiting_screening_questions',
            self::SCREENING_QUESTIONS => 'enum.selection_process.stage.screening_questions',
            self::AWAITING_HR_INTERVIEW => 'enum.selection_process.stage.awaiting_hr_interview',
            self::HR_INTERVIEW => 'enum.selection_process.stage.hr_interview',
            self::AWAITING_TECHNICAL_CHALLENGE => 'enum.selection_process.stage.awaiting_technical_challenge',
            self::TECHNICAL_CHALLENGE => 'enum.selection_process.stage.technical_challenge',
            self::AWAITING_LIVE_CODING => 'enum.selection_process.stage.awaiting_live_coding',
            self::LIVE_CODING => 'enum.selection_process.stage.live_coding',
            self::AWAITING_TECHNICAL_INTERVIEW => 'enum.selection_process.stage.awaiting_technical_interview',
            self::TECHNICAL_INTERVIEW => 'enum.selection_process.stage.technical_interview',
            self::AWAITING_BEHAVIORAL_INTERVIEW => 'enum.selection_process.stage.awaiting_behavioral_interview',
            self::BEHAVIORAL_INTERVIEW => 'enum.selection_process.stage.behavioral_interview',
            self::AWAITING_CULTURAL_FIT_INTERVIEW => 'enum.selection_process.stage.awaiting_cultural_fit_interview',
            self::CULTURAL_FIT_INTERVIEW => 'enum.selection_process.stage.cultural_fit_interview',
            self::AWAITING_GROUP_DYNAMIC => 'enum.selection_process.stage.awaiting_group_dynamic',
            self::GROUP_DYNAMIC => 'enum.selection_process.stage.group_dynamic',
            self::AWAITING_LANGUAGE_ASSESSMENT => 'enum.selection_process.stage.awaiting_language_assessment',
            self::LANGUAGE_ASSESSMENT => 'enum.selection_process.stage.language_assessment',
            self::AWAITING_PORTFOLIO_REVIEW => 'enum.selection_process.stage.awaiting_portfolio_review',
            self::PORTFOLIO_REVIEW => 'enum.selection_process.stage.portfolio_review',
            self::AWAITING_TEAM_INTERVIEW => 'enum.selection_process.stage.awaiting_team_interview',
            self::TEAM_INTERVIEW => 'enum.selection_process.stage.team_interview',
            self::AWAITING_FINAL_INTERVIEW => 'enum.selection_process.stage.awaiting_final_interview',
            self::FINAL_INTERVIEW => 'enum.selection_process.stage.final_interview',
            self::HIRED => 'enum.selection_process.stage.hired'
        };
    }
}
