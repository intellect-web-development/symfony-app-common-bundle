<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Security\PlatformToken;

use DateTimeImmutable;

/** Проверенные claims персонального токена интеграционной системы */
final readonly class PlatformTokenClaims
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public string $subject,
        public array $scopes,
        public int $generation,
        public DateTimeImmutable $expiresAt,
        public string $tokenId,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
