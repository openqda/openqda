<?php

namespace App\Enums;

enum ContentType: string
{
    case TEXT = 'text';
    case PICTURE = 'picture';
    case PDF = 'pdf';
    case AUDIO = 'audio';
    case VIDEO = 'video';

    /**
     * Get all values of the enum.
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
