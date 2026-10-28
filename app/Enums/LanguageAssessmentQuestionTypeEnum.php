<?php

namespace App\Enums;

use App\Traits\EnumHelper;

/**
 * Tipos de pergunta da prova de idioma. Todos são corrigidos automaticamente — é o
 * que diferencia a prova do questionário de triagem, que não tem gabarito
 */
enum LanguageAssessmentQuestionTypeEnum: string
{
    use EnumHelper;

    case SINGLE_CHOICE = 'single_choice';
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TEXT = 'text';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE_CHOICE => 'Escolha única',
            self::MULTIPLE_CHOICE => 'Múltipla escolha',
            self::TEXT => 'Resposta escrita'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::SINGLE_CHOICE => 'enum.language_assessment_question.type.single_choice',
            self::MULTIPLE_CHOICE => 'enum.language_assessment_question.type.multiple_choice',
            self::TEXT => 'enum.language_assessment_question.type.text'
        };
    }

    /**
     * Indica se a pergunta é respondida escolhendo entre as alternativas cadastradas
     * pela empresa, que carregam o gabarito em `is_correct`
     */
    public function hasOptions(): bool
    {
        return $this !== self::TEXT;
    }

    /**
     * Indica se a pergunta aceita mais de uma alternativa marcada na resposta
     */
    public function allowsMultipleOptions(): bool
    {
        return $this === self::MULTIPLE_CHOICE;
    }

    /**
     * Indica se a pergunta é respondida com texto escrito, conferido contra a lista
     * de respostas aceitas da própria pergunta
     */
    public function expectsText(): bool
    {
        return $this === self::TEXT;
    }
}
