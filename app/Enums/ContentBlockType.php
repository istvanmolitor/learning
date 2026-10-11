<?php

namespace App\Enums;

enum ContentBlockType: string
{
    case Heading = 'heading';
    case TextBlock = 'text';
    case Image = 'image';
    case YoutubeVideo = 'youtube_video';
}
