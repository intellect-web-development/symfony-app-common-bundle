<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Security\PlatformToken;

use RuntimeException;

/** Токен платформы не прошёл проверку: подпись, срок, аудитория или scope */
final class PlatformTokenException extends RuntimeException
{
}
