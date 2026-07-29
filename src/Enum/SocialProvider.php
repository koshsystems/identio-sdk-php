<?php

declare(strict_types=1);

namespace Identio\Sdk\Enum;

enum SocialProvider: string
{
    case Google = 'GOOGLE';
    case Yandex = 'YANDEX';
    case Vk = 'VK';
    case Facebook = 'FACEBOOK';
}
