<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum FreelanceJobTypeEnum: string
{
    
    use EnumHelper;

    case DEVELOPMENT = 'development';
    case MAINTENANCE = 'maintenance';
    case SUPPORT = 'support';
    case QA = 'qa';
    case DESIGN = 'design';
    case DEVOPS = 'devops';
    case DATABASE = 'database';
    case CONSULTING = 'consulting';
    case DOCUMENTATION = 'documentation';
    case AUTOMATION = 'automation';
    case INTEGRATION = 'integration';
    case MIGRATION = 'migration';
    case TRAINING = 'training';
    case OTHER = 'other';

    public function i18nKey(): string
    {
        return match ($this) {
            self::DEVELOPMENT => 'enum.job_type.development',
            self::MAINTENANCE => 'enum.job_type.maintenance',
            self::SUPPORT => 'enum.job_type.support',
            self::QA => 'enum.job_type.qa',
            self::DESIGN => 'enum.job_type.design',
            self::DEVOPS => 'enum.job_type.devops',
            self::DATABASE => 'enum.job_type.database',
            self::CONSULTING => 'enum.job_type.consulting',
            self::DOCUMENTATION => 'enum.job_type.documentation',
            self::AUTOMATION => 'enum.job_type.automation',
            self::INTEGRATION => 'enum.job_type.integration',
            self::MIGRATION => 'enum.job_type.migration',
            self::TRAINING => 'enum.job_type.training',
            self::OTHER => 'enum.job_type.other',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DEVELOPMENT => 'Development',
            self::MAINTENANCE => 'Maintenance',
            self::SUPPORT => 'Support',
            self::QA => 'QA',
            self::DESIGN => 'Design',
            self::DEVOPS => 'DevOps',
            self::DATABASE => 'Database',
            self::CONSULTING => 'Consulting',
            self::DOCUMENTATION => 'Documentation',
            self::AUTOMATION => 'Automation',
            self::INTEGRATION => 'Integration',
            self::MIGRATION => 'Migration',
            self::TRAINING => 'Training',
            self::OTHER => 'Other',
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::DEVELOPMENT => 'Desenvolvimento',
            self::MAINTENANCE => 'Manutenção',
            self::SUPPORT => 'Suporte',
            self::QA => 'QA',
            self::DESIGN => 'Design',
            self::DEVOPS => 'DevOps',
            self::DATABASE => 'Banco de Dados',
            self::CONSULTING => 'Consultoria',
            self::DOCUMENTATION => 'Documentação',
            self::AUTOMATION => 'Automação',
            self::INTEGRATION => 'Integração',
            self::MIGRATION => 'Migração',
            self::TRAINING => 'Treinamento',
            self::OTHER => 'Outro',
        };
    }

    // Retorna os tipos de vagas que faz sentido uma linguagem ou stack específica
    public static function requireStack(): array
    {
        return [
            self::DEVELOPMENT,
            self::MAINTENANCE,
            self::QA,
            self::DEVOPS,
            self::DATABASE,
            self::AUTOMATION,
            self::INTEGRATION,
            self::MIGRATION,
        ];
    }

    // Mesma lista de requireStack(), porém já em string, para comparar com o valor cru vindo do request
    public static function requireStackValues(): array
    {
        return array_map(fn(self $case) => $case->value, self::requireStack());
    }

    public function requiresStack(): bool
    {
        return in_array($this, self::requireStack(), true);
    }
}
