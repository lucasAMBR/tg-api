<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum ScreeningQuestionTypeEnum: string
{
    use EnumHelper;

    case ESSAY = 'essay';
    case SINGLE_CHOICE = 'single_choice';
    case MULTIPLE_CHOICE = 'multiple_choice';

    public function label(): string
    {
        return match ($this) {
            self::ESSAY => 'Dissertativa',
            self::SINGLE_CHOICE => 'Escolha única',
            self::MULTIPLE_CHOICE => 'Múltipla escolha'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::ESSAY => 'enum.screening_question.type.essay',
            self::SINGLE_CHOICE => 'enum.screening_question.type.single_choice',
            self::MULTIPLE_CHOICE => 'enum.screening_question.type.multiple_choice'
        };
    }

    /**
     * Indica se a pergunta é respondida escolhendo entre as opções cadastradas pela
     * empresa. A dissertativa é a única respondida com texto livre
     */
    public function hasOptions(): bool
    {
        return $this !== self::ESSAY;
    }

    /**
     * Indica se a pergunta aceita mais de uma opção marcada na resposta
     */
    public function allowsMultipleOptions(): bool
    {
        return $this === self::MULTIPLE_CHOICE;
    }
}
