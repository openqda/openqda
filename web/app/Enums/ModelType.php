<?php

namespace App\Enums;

enum ModelType: string
{
    case Project = 'project';
    case User = 'user';
    case Codebook = 'codebook';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
