<?php

namespace App\Contracts;

interface Translatable
{
    public function getTranslatableContent(): array;

    public function applyTranslation(array $translatedData):void;

    public function updateTranslationStatus(string $status):void;
}