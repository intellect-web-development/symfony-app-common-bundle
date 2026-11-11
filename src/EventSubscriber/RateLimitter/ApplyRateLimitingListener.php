<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\RateLimitter;

use IWD\SymfonyAppCommon\Service\Security\AuthenticatedUserIdResolverInterface;
use IWD\SymfonyAppCommon\Exception\Http\ToManyRequestException;
use IWD\SymfonyAppCommon\Service\Json\JsonDecoder;
use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onRateLimit', priority: 80)]
readonly class ApplyRateLimitingListener
{
    private array $whiteListIp;

    public function __construct(
        private AuthenticatedUserIdResolverInterface $authenticatedUserIdResolver,
        /** @var RateLimiterFactory[] */
        private array $rateLimiterClassMap,
        #[Autowire('%env(resolve:RATE_LIMIT_WHITE_LIST_IP)%')]
        string $whiteListIp,
    ) {
        try {
            $this->whiteListIp = JsonDecoder::decodeAssociative($whiteListIp);
        } catch (JsonException) {
            $this->whiteListIp = [];
        }
    }

    public function onRateLimit(KernelEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $ip = $request->getClientIp();
        if (null !== $ip && in_array($ip, $this->whiteListIp)) {
            return;
        }

        /** @var string $controllerClass */
        $controllerClass = $request->attributes->get('_controller');

        $routeName = $request->attributes->get('_route') ?? 'undefined';

        $rateLimiter = $this->rateLimiterClassMap[$controllerClass] ?? null;
        if (null === $rateLimiter) {
            return; // этому экшену не назначена служба ограничения количества запросов
        }

        $userIdentity = $this->authenticatedUserIdResolver->tryResolveAuthenticatedUserId($request);

        if (null === $userIdentity) {
            $limit = $rateLimiter->create($routeName . ':' . $request->getClientIp())->consume();
        } else {
            $limit = $rateLimiter->create($routeName . ':' . $userIdentity)->consume();
        }

        if (false === $limit->isAccepted()) {
            throw new ToManyRequestException($limit);
        }
    }
}
