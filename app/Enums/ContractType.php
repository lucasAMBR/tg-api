<?php

namespace App\Enums;

use App\Traits\EnumHelper;

// Referente aos TIPOS DE CONTRATAÇÃO Possiveis
enum ContractType: string
{
    use EnumHelper;

    case CLT = "clt";
    case CONTRACTOR = "contractor";
    case INTERNSHIP = "intership";

    public function label(): string
    {
        return match($this){
            self::CLT => "Clt",
            self::CONTRACTOR => "Contractor",
            self::INTERNSHIP => "Internship",
        };
    }
}
