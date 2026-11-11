<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Security\PlatformToken;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Единая точка проверки токена на межсервисных маршрутах (например, /api/sdk).
 *
 * Конкретный сервис наследует класс и задаёт префикс, аудиторию и scope каждого маршрута.
 * Маршрут под префиксом без scope отклоняется: новый эндпоинт не окажется открытым по забывчивости.
 * Код вызывающей системы берётся только из `sub` токена, отдельных заголовков нет.
 */
abstract class AbstractPlatformTokenSubscriber implements EventSubscriberInterface
{
    /** Атрибут запроса с PlatformTokenClaims */
    public const string CLAIMS_ATTRIBUTE = '_platform_token_claims';

    /** Признак межсервисного маршрута: общий лимитер по пользовательскому JWT его пропускает (имя продублировано там строкой) */
    public const string ENDPOINT_ATTRIBUTE = '_platform_token_endpoint';

    public function __construct(
        private readonly PlatformTokenVerifier $verifier,
        #[Autowire(service: 'limiter.platform_sdk_ip')]
        private readonly RateLimiterFactory $ipLimiter,
        #[Autowire(service: 'limiter.platform_sdk_subject')]
        private readonly RateLimiterFactory $subjectLimiter,
    ) {
    }

    /** Префикс пути, например /api/sdk/ */
    abstract protected function pathPrefix(): string;

    /** Аудитория токена: имя этого сервиса */
    abstract protected function audience(): string;

    /** @return array<string, string> имя маршрута => требуемый scope */
    abstract protected function routeScopes(): array;

    public static function getSubscribedEvents(): array
    {
        // После RouterListener (32), чтобы был известен _route
        return [KernelEvents::REQUEST => ['onKernelRequest', 16]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), $this->pathPrefix())) {
            return;
        }

        $request->attributes->set(self::ENDPOINT_ATTRIBUTE, true);

        // Лимит по IP до разбора токена: гасит перебор и мусорный трафик
        if (!$this->ipLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many requests.');
        }

        $scope = $this->routeScopes()[(string) $request->attributes->get('_route')] ?? null;
        if (null === $scope) {
            throw new AccessDeniedHttpException('Route is not available for platform tokens.');
        }

        $jwt = $this->extractBearer($request);
        try {
            $claims = $this->verifier->verify(jwt: $jwt, audience: $this->audience(), requiredScope: $scope);
        } catch (PlatformTokenException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        if (!$this->subjectLimiter->create($claims->subject)->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many requests.');
        }
        $request->attributes->set(self::CLAIMS_ATTRIBUTE, $claims);
    }

    private function extractBearer(Request $request): string
    {
        $header = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($header, 'Bearer ') || '' === ($jwt = trim(substr($header, 7)))) {
            throw new UnauthorizedHttpException(challenge: 'Bearer', message: 'Bearer token is required.');
        }

        return $jwt;
    }
}
