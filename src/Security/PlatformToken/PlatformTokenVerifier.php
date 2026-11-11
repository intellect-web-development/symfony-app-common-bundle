<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Security\PlatformToken;

use DateTimeImmutable;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use UnexpectedValueException;

/**
 * Проверяет персональные JWT интеграционных систем (EdDSA), которые выпускает integration-registry.
 *
 * Знает только публичный ключ, подписать токен не может. Отзыв по поколению (`gen`)
 * и статус системы проверяет вызывающий: у него есть проекция реестра.
 */
final readonly class PlatformTokenVerifier
{
    public const string ISSUER = 'integration-registry';

    public function __construct(
        #[Autowire('%env(PLATFORM_JWT_PUBLIC_KEY)%')]
        private string $publicKey,
    ) {
    }

    /**
     * @throws PlatformTokenException
     */
    public function verify(string $jwt, string $audience, string $requiredScope): PlatformTokenClaims
    {
        if ('' === $this->publicKey) {
            // Пустой ключ нельзя трактовать как «проверки нет»: межсервисные вызовы получают 500, остальной сервис работает
            throw new LogicException('PLATFORM_JWT_PUBLIC_KEY is not configured');
        }
        try {
            $payload = JWT::decode(jwt: $jwt, keyOrKeyArray: new Key(keyMaterial: $this->publicKey, algorithm: 'EdDSA'));
        } catch (UnexpectedValueException|DomainException|InvalidArgumentException $exception) {
            throw new PlatformTokenException(message: 'Invalid platform token: ' . $exception->getMessage(), code: $exception->getCode(), previous: $exception);
        }

        if (self::ISSUER !== ($payload->iss ?? null)) {
            throw new PlatformTokenException('Unexpected token issuer.');
        }
        if (!isset($payload->exp) || !is_int($payload->exp)) {
            // Бессрочные токены не принимаем, даже если подпись верна
            throw new PlatformTokenException('Token has no expiration.');
        }
        if (!is_string($payload->sub ?? null) || '' === $payload->sub) {
            throw new PlatformTokenException('Token has no subject.');
        }
        if (!in_array($audience, (array) ($payload->aud ?? []), true)) {
            throw new PlatformTokenException('Token is issued for another audience.');
        }
        $scopes = array_values(array_filter((array) ($payload->scp ?? []), is_string(...)));
        if (!in_array($requiredScope, $scopes, true)) {
            throw new PlatformTokenException(sprintf('Token has no scope "%s".', $requiredScope));
        }
        if (!is_int($payload->gen ?? null)) {
            throw new PlatformTokenException('Token has no generation.');
        }

        return new PlatformTokenClaims(
            subject: $payload->sub,
            scopes: $scopes,
            generation: $payload->gen,
            expiresAt: (new DateTimeImmutable())->setTimestamp($payload->exp),
            tokenId: (string) ($payload->jti ?? ''),
        );
    }
}
