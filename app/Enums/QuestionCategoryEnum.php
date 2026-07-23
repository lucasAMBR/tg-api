<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum QuestionCategoryEnum: string
{
    use EnumHelper;

    case BACKEND_C_SHARP_ASP = 'backend_c_sharp_asp';
    case BACKEND_DATA_HANDLING = 'backend_data_handling';
    case BACKEND_JAVA_SPRING = 'backend_java_spring';
    case BACKEND_PHP_LARAVEL = 'backend_php_laravel';
    case BACKEND_PYTHON_FAST_API = 'backend_python_fast_api';
    case BACKEND_TYPESCRIPT = 'backend_typescript';
    case CLEAN_CODE = 'clean_code';
    case DEVOPS = 'devops';
    case FRONTEND_ANGULAR = 'frontend_angular';
    case FRONTEND_FUNDAMENTALS = 'frontend_fundamentals';
    case FRONTEND_REACT = 'frontend_react';
    case PROGRAMMING_LOGIC = 'programming_logic';
    case SECURITY_BACKEND = 'security_backend';
    case SECURITY_FRONTEND = 'security_frontend';

    public function i18nKey(): string
    {
        return match ($this) {
            self::BACKEND_C_SHARP_ASP => 'enum.question_category.backend_c_sharp_asp',
            self::BACKEND_DATA_HANDLING => 'enum.question_category.backend_data_handling',
            self::BACKEND_JAVA_SPRING => 'enum.question_category.backend_java_spring',
            self::BACKEND_PHP_LARAVEL => 'enum.question_category.backend_php_laravel',
            self::BACKEND_PYTHON_FAST_API => 'enum.question_category.backend_python_fast_api',
            self::BACKEND_TYPESCRIPT => 'enum.question_category.backend_typescript',
            self::CLEAN_CODE => 'enum.question_category.clean_code',
            self::DEVOPS => 'enum.question_category.devops',
            self::FRONTEND_ANGULAR => 'enum.question_category.frontend_angular',
            self::FRONTEND_FUNDAMENTALS => 'enum.question_category.frontend_fundamentals',
            self::FRONTEND_REACT => 'enum.question_category.frontend_react',
            self::PROGRAMMING_LOGIC => 'enum.question_category.programming_logic',
            self::SECURITY_BACKEND => 'enum.question_category.security_backend',
            self::SECURITY_FRONTEND => 'enum.question_category.security_frontend',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BACKEND_C_SHARP_ASP => 'Backend C# ASP.NET',
            self::BACKEND_DATA_HANDLING => 'Backend Data Handling',
            self::BACKEND_JAVA_SPRING => 'Backend Java Spring',
            self::BACKEND_PHP_LARAVEL => 'Backend PHP Laravel',
            self::BACKEND_PYTHON_FAST_API => 'Backend Python FastAPI',
            self::BACKEND_TYPESCRIPT => 'Backend TypeScript',
            self::CLEAN_CODE => 'Clean Code',
            self::DEVOPS => 'DevOps',
            self::FRONTEND_ANGULAR => 'Frontend Angular',
            self::FRONTEND_FUNDAMENTALS => 'Frontend Fundamentals',
            self::FRONTEND_REACT => 'Frontend React',
            self::PROGRAMMING_LOGIC => 'Programming Logic',
            self::SECURITY_BACKEND => 'Security Backend',
            self::SECURITY_FRONTEND => 'Security Frontend',
        };
    }

    /**
     * Categorias de stacks específicas selecionáveis por um dev frontend.
     *
     * @return array<int, self>
     */
    public static function frontendStacks(): array
    {
        return [
            self::FRONTEND_ANGULAR,
            self::FRONTEND_REACT,
        ];
    }

    /**
     * Categorias de stacks específicas selecionáveis por um dev backend.
     *
     * @return array<int, self>
     */
    public static function backendStacks(): array
    {
        return [
            self::BACKEND_C_SHARP_ASP,
            self::BACKEND_JAVA_SPRING,
            self::BACKEND_PHP_LARAVEL,
            self::BACKEND_PYTHON_FAST_API,
            self::BACKEND_TYPESCRIPT,
        ];
    }

    /**
     * Categorias gerais, não atreladas a uma stack específica, selecionáveis por qualquer dev.
     *
     * @return array<int, self>
     */
    public static function generalStacks(): array
    {
        return [
            self::BACKEND_DATA_HANDLING,
            self::CLEAN_CODE,
            self::DEVOPS,
            self::FRONTEND_FUNDAMENTALS,
            self::PROGRAMMING_LOGIC,
            self::SECURITY_BACKEND,
            self::SECURITY_FRONTEND,
        ];
    }

    /**
     * Stacks selecionáveis agrupadas por área (frontend/backend), de acordo com a
     * especialidade do dev. Cada item segue o formato { value, i18n_key }.
     *
     * @return array<string, array<int, array{value: string, i18n_key: string}>>
     */
    public static function stacksBySpecialty(DevSpecialtyEnum $specialty): array
    {
        $groups = [
            'frontend' => self::frontendStacks(),
            'backend' => self::backendStacks(),
        ];

        $areas = match ($specialty) {
            DevSpecialtyEnum::FRONTEND => ['frontend'],
            DevSpecialtyEnum::BACKEND => ['backend'],
            DevSpecialtyEnum::FULLSTACK => ['frontend', 'backend'],
        };

        $result = [];

        foreach ($areas as $area) {
            $result[$area] = array_map(fn (self $case) => [
                'value' => $case->value,
                'i18n_key' => $case->i18nKey(),
            ], $groups[$area]);
        }

        return $result;
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::BACKEND_C_SHARP_ASP => 'Backend C# ASP.NET',
            self::BACKEND_DATA_HANDLING => 'Backend Manipulação de Dados',
            self::BACKEND_JAVA_SPRING => 'Backend Java Spring',
            self::BACKEND_PHP_LARAVEL => 'Backend PHP Laravel',
            self::BACKEND_PYTHON_FAST_API => 'Backend Python FastAPI',
            self::BACKEND_TYPESCRIPT => 'Backend TypeScript',
            self::CLEAN_CODE => 'Clean Code',
            self::DEVOPS => 'DevOps',
            self::FRONTEND_ANGULAR => 'Frontend Angular',
            self::FRONTEND_FUNDAMENTALS => 'Fundamentos de Frontend',
            self::FRONTEND_REACT => 'Frontend React',
            self::PROGRAMMING_LOGIC => 'Lógica de Programação',
            self::SECURITY_BACKEND => 'Segurança Backend',
            self::SECURITY_FRONTEND => 'Segurança Frontend',
        };
    }
}
