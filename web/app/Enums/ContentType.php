<?php

namespace App\Enums;

enum ContentType: string
{
    case TEXT = 'text';
    case PICTURE = 'picture';
    case PDF = 'pdf';
    case AUDIO = 'audio';
    case VIDEO = 'video';
}
