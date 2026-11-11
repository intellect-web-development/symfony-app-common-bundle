<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\RateLimitter;

use IWD\SymfonyAppCommon\Service\Security\AuthenticatedUserIdResolverInterface;
use IWD\SymfonyAppCommon\Exception\Http\ToManyRequestException;
use IWD\SymfonyAppCommon\Service\Json\JsonDecoder;
use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onRateLimit', priority: 90)]
readonly class RateLimitPerHourHttpCallSubscriber
{
    private array $whiteListIp;

    public function __construct(
        private RateLimiterFactory $anonymousApiCommon,
        private RateLimiterFactory $authenticatedApiCommon,
        private AuthenticatedUserIdResolverInterface $authenticatedUserIdResolver,
        #[Autowire('%env(resolve:RATE_LIMIT_WHITE_LIST_IP)%')]
        string $whiteListIp,
    ) {
        try {
            $this->whiteListIp = JsonDecoder::decodeAssociative($whiteListIp);
        } catch (JsonException) {
            $this->whiteListIp = [];
        }
    }

    public function onRateLimit(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Межсервисные маршруты (/api/sdk): токен платформы не пользовательский JWT, лимиты по IP и системе ставит AbstractPlatformTokenSubscriber
        if (true === $request->attributes->get('_platform_token_endpoint')) {
            return;
        }

        $ip = $request->getClientIp();
        if (null !== $ip && in_array($ip, $this->whiteListIp)) {
            return;
        }

        $userIdentity = $this->authenticatedUserIdResolver->tryResolveAuthenticatedUserId($request);

        if (null === $userIdentity) {
            $limiter = $this->anonymousApiCommon->create($request->getClientIp());
        } else {
            $limiter = $this->authenticatedApiCommon->create($userIdentity);
        }

        $limit = $limiter->consume();

        if (false === $limit->isAccepted()) {
            throw new ToManyRequestException($limit);
        }
    }
}
